<?php
/**
 * ChatController - Proxy & API Handler untuk Chatbot FaceAbsen AI
 */
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/ApiKey.php';

class ChatController extends Controller {

    private $nodeServerUrl = 'http://127.0.0.1:3001';
    private $nvidiaApiKey = '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';
    private $nvidiaModel = 'deepseek-ai/deepseek-v4.1-flash';
    private $nvidiaFallbackModel = 'meta/llama-3.2-11b-vision-instruct';

    private const CODING_REFUSAL_MESSAGE = 'Maaf, saya tidak diizinkan untuk membuat atau membahas kode program.';

    /**
     * Mendapatkan info API Key aktif dari database (mendukung Google Gemini dan NVIDIA NIM)
     */
    private function getActiveApiKeyInfo() {
        try {
            $active = ApiKey::getActiveKey();
            if (!empty($active['api_key'])) {
                $key = $active['api_key'];
                $prov = $active['provider'] ?? '';
                $isGemini = (stripos($prov, 'gemini') !== false || stripos($prov, 'google') !== false || strpos($key, 'AQ.') === 0 || strpos($key, 'AIza') === 0);
                return [
                    'api_key' => $key,
                    'provider' => $prov ?: ($isGemini ? 'Google Gemini' : 'NVIDIA NIM'),
                    'is_gemini' => $isGemini,
                    'name' => $active['name'] ?? ''
                ];
            }
        } catch (Throwable $e) {}

        return [
            'api_key' => $this->nvidiaApiKey,
            'provider' => 'NVIDIA NIM',
            'is_gemini' => false,
            'name' => 'Default'
        ];
    }

    /**
     * Mendapatkan API Key NVIDIA NIM aktif dari database
     */
    private function getActiveNvidiaApiKey() {
        $info = $this->getActiveApiKeyInfo();
        if (!$info['is_gemini']) {
            return $info['api_key'];
        }
        return $this->nvidiaApiKey;
    }

    public function index() {
        $this->health();
    }

    /**
     * Deteksi permintaan koding / pemrograman
     */
    private function isCodingRequest($text) {
        if (empty($text)) return false;
        $patterns = [
            '/\b(coding|koding|kodingkan|pemrograman|programmer)\b/i',
            '/\b(bikin|buat|buatkan|tulis|tuliskan|contohkan|kasih|generate)\s+(kode|code|skrip|script|program|fungsi|function|algoritma|query)\b/i',
            '/\b(source\s*code|sintaks|syntax|query\s+sql|buatkan\s+query)\b/i',
            '/\b(javascript|python|php|html|css|java|c\+\+|c#|golang|bash|shell|typescript)\b/i',
            '/\b(console\.log|echo\s+[\'"]|print\(|def\s+[a-z_]|class\s+[a-z_]|import\s+[a-z_]|<\?php)\b/i'
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Deteksi dan jawab pertanyaan identitas ("siapa saya" / "siapa anda")
     */
    private function getIdentityResponse($message, $userName, $userRole) {
        $lower = strtolower(trim($message ?? ''));
        $roleStr = strtolower($userRole ?? '');
        $isSuperUser = (strpos($roleStr, 'super_user') !== false || strpos($roleStr, 'admin') !== false);
        $isGuru = (strpos($roleStr, 'guru') !== false || $roleStr === 'teacher');

        $isSiapaSaya = preg_match('/\b(siapa\s+(saya|aku|diriku)|(saya|aku)\s+siapa|siapakah\s+(saya|aku|diriku)|identitas\s+saya|siapa\s+nama\s+saya|siapakah\s+nama\s+saya)\b/i', $lower);
        $isSiapaAnda = preg_match('/\b(siapa\s+(anda|kamu|kau|dirimu|bot|chatbot)|(anda|kamu|kau)\s+siapa|siapakah\s+(anda|kamu|kau|dirimu|bot|chatbot)|identitas\s+(anda|kamu)|siapa\s+nama\s+(anda|kamu)|nama\s+kamu\s+siapa)\b/i', $lower);

        $jabatan = 'Peserta Didik (Siswa)';
        if ($isSuperUser) {
            $jabatan = 'Super User (Administrator)';
        } elseif ($isGuru) {
            $jabatan = 'Guru / Tenaga Pendidik';
        }

        if ($isSiapaSaya && $isSiapaAnda) {
            return "Anda adalah {$userName}, dengan jabatan sebagai {$jabatan}. Saya adalah FaceAbsen AI, asisten virtual untuk sistem presensi sekolah FaceAbsen.";
        }
        if ($isSiapaSaya) {
            return "Anda adalah {$userName}, dengan jabatan sebagai {$jabatan}.";
        }
        if ($isSiapaAnda) {
            return 'Saya adalah FaceAbsen AI, asisten virtual untuk sistem presensi sekolah FaceAbsen.';
        }

        return null;
    }

    /**
     * Helper cerdas: Membedakan pertanyaan jumlah ("berapa banyak" -> angka) dan daftar nama ("sebutkan namanya" / "siapa saja" -> daftar nama) untuk Super User
     */
    private function handleSuperUserQuery($message, $history = []) {
        $rawLower = strtolower(trim($message ?? ''));
        $lower = preg_replace('/[?!.]+$/', '', $rawLower);
        $lower = trim($lower);

        $isBerapaBanyak = preg_match('/\b(berapa|jumlah|total|banyaknya)\b/i', $lower) || (strpos($lower, 'ada berapa') === 0);
        $isSebutkanNama = preg_match('/\b(sebutkan|daftar|siapa\s+saja|mana\s+saja|nama(-|\s*)namanya|namanya|tampilkan\s+nama|siapa\s+yang)\b/i', $lower) ||
                         (strpos($lower, 'siapa ') === 0) || (strpos($lower, 'siapakah ') === 0);

        if (!$isBerapaBanyak && !$isSebutkanNama) {
            return null;
        }

        $topic = null;
        if (strpos($lower, 'terlambat') !== false || strpos($lower, 'telat') !== false) {
            $topic = 'terlambat';
        } elseif (strpos($lower, 'belum') !== false || strpos($lower, 'tidak hadir') !== false) {
            $topic = 'belum_hadir';
        } elseif (strpos($lower, 'hadir') !== false || strpos($lower, 'masuk') !== false || strpos($lower, 'kehadiran') !== false || (strpos($lower, 'absen') !== false && strpos($lower, 'belum') === false)) {
            $topic = 'hadir';
        } elseif (strpos($lower, 'siswa') !== false || strpos($lower, 'peserta didik') !== false || strpos($lower, 'murid') !== false) {
            $topic = 'siswa';
        } elseif (strpos($lower, 'guru') !== false || strpos($lower, 'pengajar') !== false || strpos($lower, 'pendidik') !== false) {
            $topic = 'guru';
        } elseif (strpos($lower, 'kelas') !== false || strpos($lower, 'rombel') !== false) {
            $topic = 'kelas';
        } elseif (strpos($lower, 'user') !== false || strpos($lower, 'pengguna') !== false || strpos($lower, 'akun') !== false) {
            $topic = 'user';
        } elseif (strpos($lower, 'setting') !== false || strpos($lower, 'pengaturan') !== false) {
            $topic = 'setting';
        } elseif (strpos($lower, 'tabel') !== false || strpos($lower, 'table') !== false) {
            $topic = 'tabel';
        }

        // Cek context history jika topic belum jelas
        if (!$topic && !empty($history) && is_array($history)) {
            $historyText = '';
            foreach (array_slice($history, -3) as $h) {
                $historyText .= ' ' . strtolower($h['content'] ?? '');
            }
            if (strpos($historyText, 'terlambat') !== false || strpos($historyText, 'telat') !== false) {
                $topic = 'terlambat';
            } elseif (strpos($historyText, 'belum') !== false) {
                $topic = 'belum_hadir';
            } elseif (strpos($historyText, 'hadir') !== false || strpos($historyText, 'absen') !== false) {
                $topic = 'hadir';
            } elseif (strpos($historyText, 'siswa') !== false || strpos($historyText, 'murid') !== false) {
                $topic = 'siswa';
            } elseif (strpos($historyText, 'guru') !== false || strpos($historyText, 'pengajar') !== false) {
                $topic = 'guru';
            } elseif (strpos($historyText, 'kelas') !== false || strpos($historyText, 'rombel') !== false) {
                $topic = 'kelas';
            } elseif (strpos($historyText, 'user') !== false || strpos($historyText, 'akun') !== false) {
                $topic = 'user';
            }
        }

        if (!$topic) return null;

        try {
            $db = new Database();

            // KASUS 1: USER TANYA "BERAPA BANYAK" / JUMLAH (HANYA ANGKA TOTAL)
            if ($isBerapaBanyak && !$isSebutkanNama) {
                if ($topic === 'siswa') {
                    $db->query("SELECT COUNT(*) as c FROM students");
                    $tot = (int)($db->single()['c'] ?? 0);
                    return "Total siswa: {$tot} orang.";
                }
                if ($topic === 'guru') {
                    $db->query("SELECT COUNT(*) as c FROM teachers");
                    $tot = (int)($db->single()['c'] ?? 0);
                    return "Total guru: {$tot} orang.";
                }
                if ($topic === 'hadir') {
                    $db->query("
                        SELECT
                            COUNT(*) as total_records,
                            COALESCE(SUM(CASE WHEN u.role = 'peserta_didik' AND a.status IN ('hadir', 'terlambat') THEN 1 ELSE 0 END), 0) as siswa_hadir,
                            COALESCE(SUM(CASE WHEN u.role = 'guru' AND a.status IN ('hadir', 'terlambat') THEN 1 ELSE 0 END), 0) as guru_hadir
                        FROM attendances a
                        JOIN users u ON a.user_id = u.id
                        WHERE a.attendance_date = CURRENT_DATE()
                    ");
                    $row = $db->single() ?: ['total_records' => 0, 'siswa_hadir' => 0, 'guru_hadir' => 0];
                    $total = (int)($row['total_records'] ?? 0);
                    $sHadir = (int)($row['siswa_hadir'] ?? 0);
                    $gHadir = (int)($row['guru_hadir'] ?? 0);
                    return "Total hadir hari ini: {$total} orang (Siswa: {$sHadir}, Guru: {$gHadir}).";
                }
                if ($topic === 'terlambat') {
                    $db->query("SELECT COUNT(*) as c FROM attendances WHERE attendance_date = CURRENT_DATE() AND status = 'terlambat'");
                    $tot = (int)($db->single()['c'] ?? 0);
                    return "Total terlambat hari ini: {$tot} orang.";
                }
                if ($topic === 'belum_hadir') {
                    $db->query("SELECT COUNT(*) as c FROM students");
                    $totS = (int)($db->single()['c'] ?? 0);
                    $db->query("SELECT COUNT(*) as c FROM teachers");
                    $totG = (int)($db->single()['c'] ?? 0);
                    $db->query("
                        SELECT
                            COALESCE(SUM(CASE WHEN u.role = 'peserta_didik' AND a.status IN ('hadir', 'terlambat') THEN 1 ELSE 0 END), 0) as s_hadir,
                            COALESCE(SUM(CASE WHEN u.role = 'guru' AND a.status IN ('hadir', 'terlambat') THEN 1 ELSE 0 END), 0) as g_hadir
                        FROM attendances a
                        JOIN users u ON a.user_id = u.id
                        WHERE a.attendance_date = CURRENT_DATE()
                    ");
                    $d = $db->single() ?: ['s_hadir' => 0, 'g_hadir' => 0];
                    $sBelum = max(0, $totS - (int)($d['s_hadir'] ?? 0));
                    $gBelum = max(0, $totG - (int)($d['g_hadir'] ?? 0));
                    $totBelum = $sBelum + $gBelum;
                    return "Total belum hadir: {$totBelum} orang (Siswa: {$sBelum}, Guru: {$gBelum}).";
                }
                if ($topic === 'kelas') {
                    $db->query("SELECT COUNT(*) as c FROM classes");
                    $tot = (int)($db->single()['c'] ?? 0);
                    return "Total kelas: {$tot} rombel.";
                }
                if ($topic === 'user') {
                    $db->query("SELECT COUNT(*) as c FROM users");
                    $tot = (int)($db->single()['c'] ?? 0);
                    return "Total akun pengguna: {$tot} akun.";
                }
                if ($topic === 'setting') {
                    $db->query("SELECT COUNT(*) as c FROM settings");
                    $tot = (int)($db->single()['c'] ?? 0);
                    return "Total pengaturan: {$tot} konfigurasi.";
                }
                if ($topic === 'tabel') {
                    return "Total tabel: 7 tabel (attendances, broadcast_messages, classes, settings, students, teachers, users).";
                }
            }

            // KASUS 2: USER TANYA "SEBUTKAN NAMANYA" / "SIAPA SAJA" (DAFTAR NAMA)
            if ($isSebutkanNama && !$isBerapaBanyak) {
                if ($topic === 'hadir') {
                    $db->query("
                        SELECT u.name, u.role, a.status, TIME_FORMAT(a.attendance_time, '%H:%i') as a_time
                        FROM attendances a
                        JOIN users u ON a.user_id = u.id
                        WHERE a.attendance_date = CURRENT_DATE()
                        ORDER BY a.attendance_time ASC
                    ");
                    $rows = $db->resultSet();
                    if (empty($rows)) {
                        return "Belum ada yang hadir hari ini.";
                    }
                    $lines = [];
                    foreach ($rows as $r) {
                        $roleLbl = $r['role'] === 'guru' ? 'Guru' : 'Siswa';
                        $st = strtoupper($r['status']);
                        $lines[] = "- {$r['name']} ({$roleLbl}) | Status: {$st} | Pukul: {$r['a_time']}";
                    }
                    return implode("\n", $lines);
                }

                if ($topic === 'terlambat') {
                    $db->query("
                        SELECT u.name, u.role, TIME_FORMAT(a.attendance_time, '%H:%i') as a_time
                        FROM attendances a
                        JOIN users u ON a.user_id = u.id
                        WHERE a.attendance_date = CURRENT_DATE() AND a.status = 'terlambat'
                        ORDER BY a.attendance_time ASC
                    ");
                    $rows = $db->resultSet();
                    if (empty($rows)) {
                        return "Tidak ada yang terlambat hari ini.";
                    }
                    $lines = [];
                    foreach ($rows as $r) {
                        $roleLbl = $r['role'] === 'guru' ? 'Guru' : 'Siswa';
                        $lines[] = "- {$r['name']} ({$roleLbl}) | Status: TERLAMBAT | Pukul: {$r['a_time']}";
                    }
                    return implode("\n", $lines);
                }

                if ($topic === 'siswa') {
                    $db->query("
                        SELECT s.nis, s.phone, u.name, c.name as class_name
                        FROM students s
                        JOIN users u ON s.user_id = u.id
                        JOIN classes c ON s.class_id = c.id
                        WHERE u.status = 'active'
                        ORDER BY c.level ASC, c.name ASC, u.name ASC
                    ");
                    $rows = $db->resultSet();
                    if (empty($rows)) return "Belum ada data siswa terdaftar.";
                    $lines = [];
                    foreach ($rows as $s) {
                        $phoneStr = !empty($s['phone']) ? ", Telp: {$s['phone']}" : "";
                        $lines[] = "- {$s['name']} (NIS: {$s['nis']}, Kelas: {$s['class_name']}{$phoneStr})";
                    }
                    return implode("\n", $lines);
                }

                if ($topic === 'guru') {
                    $db->query("
                        SELECT t.nip, t.phone, u.name
                        FROM teachers t
                        JOIN users u ON t.user_id = u.id
                        WHERE u.status = 'active'
                        ORDER BY u.name ASC
                    ");
                    $rows = $db->resultSet();
                    if (empty($rows)) return "Belum ada data guru terdaftar.";
                    $lines = [];
                    foreach ($rows as $t) {
                        $phoneStr = !empty($t['phone']) ? ", Telp: {$t['phone']}" : "";
                        $lines[] = "- {$t['name']} (NIP: {$t['nip']}{$phoneStr})";
                    }
                    return implode("\n", $lines);
                }

                if ($topic === 'kelas') {
                    $db->query("SELECT name, level FROM classes ORDER BY level ASC, name ASC");
                    $rows = $db->resultSet();
                    if (empty($rows)) return "Belum ada data kelas terdaftar.";
                    $lines = [];
                    foreach ($rows as $c) {
                        $lines[] = "- Kelas {$c['name']} (Tingkat {$c['level']})";
                    }
                    return implode("\n", $lines);
                }

                if ($topic === 'user') {
                    $db->query("SELECT name, username, role FROM users ORDER BY FIELD(role, 'super_user', 'guru', 'peserta_didik'), name ASC");
                    $rows = $db->resultSet();
                    if (empty($rows)) return "Belum ada data pengguna terdaftar.";
                    $lines = [];
                    foreach ($rows as $u) {
                        $r = strtoupper($u['role']);
                        $lines[] = "- [{$r}] {$u['name']} (username: {$u['username']})";
                    }
                    return implode("\n", $lines);
                }
            }

            // KASUS 3: JUMLAH SEKALIGUS NAMA
            if ($isBerapaBanyak && $isSebutkanNama) {
                if ($topic === 'siswa') {
                    $db->query("SELECT s.nis, s.phone, u.name, c.name as class_name FROM students s JOIN users u ON s.user_id = u.id JOIN classes c ON s.class_id = c.id WHERE u.status = 'active'");
                    $rows = $db->resultSet();
                    $tot = count($rows);
                    $lines = ["Total siswa: {$tot} orang.\nDaftar nama siswa:"];
                    foreach ($rows as $s) {
                        $phoneStr = !empty($s['phone']) ? ", Telp: {$s['phone']}" : "";
                        $lines[] = "- {$s['name']} (NIS: {$s['nis']}, Kelas: {$s['class_name']}{$phoneStr})";
                    }
                    return implode("\n", $lines);
                }
                if ($topic === 'guru') {
                    $db->query("SELECT t.nip, t.phone, u.name FROM teachers t JOIN users u ON t.user_id = u.id WHERE u.status = 'active'");
                    $rows = $db->resultSet();
                    $tot = count($rows);
                    $lines = ["Total guru: {$tot} orang.\nDaftar nama guru:"];
                    foreach ($rows as $t) {
                        $phoneStr = !empty($t['phone']) ? ", Telp: {$t['phone']}" : "";
                        $lines[] = "- {$t['name']} (NIP: {$t['nip']}{$phoneStr})";
                    }
                    return implode("\n", $lines);
                }
            }

        } catch (Throwable $e) {
            return null;
        }

        return null;
    }

    /**
     * Endpoint: POST /chat/send
     * Menerima pesan pengguna dan meneruskannya ke Node.js Chatbot service atau direct fallback
     */
    public function send() {
        // Cek login pengguna
        if (!Auth::check()) {
            return $this->json([
                'success' => false,
                'error' => 'Akses ditolak. Silakan login terlebih dahulu.'
            ], 401);
        }

        // Ambil data payload JSON atau POST form
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        if (!$data && !empty($_POST)) {
            $data = $_POST;
        }

        $message = trim($data['message'] ?? '');
        $history = isset($data['history']) && is_array($data['history']) ? $data['history'] : [];

        if (empty($message)) {
            return $this->json([
                'success' => false,
                'error' => 'Pesan tidak boleh kosong.'
            ], 400);
        }

        // 1. CEK ATURAN: JANGAN IZINKAN CODING APAPUN
        if ($this->isCodingRequest($message)) {
            return $this->json([
                'success' => true,
                'reply' => self::CODING_REFUSAL_MESSAGE,
                'model' => $this->nvidiaModel
            ]);
        }

        $currentUser = Auth::user();
        $userName = $currentUser['name'] ?? 'Pengguna';
        $userRole = $currentUser['role'] ?? 'peserta_didik';
        $isSuperUser = (strpos($userRole, 'super_user') !== false || strpos($userRole, 'admin') !== false);

        // 2. CEK PERTANYAAN IDENTITAS ("siapa saya" / "siapa anda")
        $identityReply = $this->getIdentityResponse($message, $userName, $userRole);
        if ($identityReply !== null) {
            return $this->json([
                'success' => true,
                'reply' => $identityReply,
                'model' => 'FaceAbsen AI'
            ]);
        }

        // 3. CEK PERTANYAAN KHUSUS SUPER USER ("berapa banyak" -> angka, "sebutkan namanya" -> daftar nama)
        if ($isSuperUser) {
            $suReply = $this->handleSuperUserQuery($message, $history);
            if ($suReply !== null) {
                return $this->json([
                    'success' => true,
                    'reply' => $suReply,
                    'model' => 'FaceAbsen AI'
                ]);
            }
        }

        // 4. Coba hubungi Node.js Service (Port 3001)
        $nodePayload = json_encode([
            'message' => $message,
            'history' => $history,
            'userName' => $userName,
            'userRole' => $userRole
        ]);

        $nodeResponse = $this->callNodeService('/api/chat', $nodePayload, 'POST', 35);
        if ($nodeResponse !== false) {
            $parsed = json_decode($nodeResponse, true);
            if (isset($parsed['reply'])) {
                // Filter tambahan jika output mengandung blok kode
                if (strpos($parsed['reply'], '```') !== false) {
                    $parsed['reply'] = self::CODING_REFUSAL_MESSAGE;
                }
                return $this->json($parsed);
            }
        }

        // 5. Fallback Langsung ke API AI Aktif (Google Gemini atau NVIDIA NIM)
        $keyInfo = $this->getActiveApiKeyInfo();
        if ($keyInfo['is_gemini']) {
            $directResponse = $this->callGeminiDirect($message, $history, $userName, $userRole, $keyInfo['api_key']);
            if (!empty($directResponse['success'])) {
                return $this->json($directResponse);
            }
        } else {
            $directResponse = $this->callNvidiaDirect($message, $history, $userName, $userRole, $keyInfo['api_key']);
            if (!empty($directResponse['success'])) {
                return $this->json($directResponse);
            }
        }

        // 6. Coba fallback silang antar provider jika provider utama gagal
        if ($keyInfo['is_gemini'] && !empty($this->nvidiaApiKey)) {
            $directResponse = $this->callNvidiaDirect($message, $history, $userName, $userRole, $this->nvidiaApiKey);
            if (!empty($directResponse['success'])) {
                return $this->json($directResponse);
            }
        } elseif (!$keyInfo['is_gemini']) {
            // Jika NVIDIA gagal, coba cari kunci Gemini di database
            try {
                $db = Database::getInstance();
                $geminiRow = $db->query("SELECT api_key FROM api_keys WHERE (provider LIKE '%gemini%' OR provider LIKE '%google%' OR api_key LIKE 'AQ.%' OR api_key LIKE 'AIza%') LIMIT 1")->fetch();
                if (!empty($geminiRow['api_key'])) {
                    $directResponse = $this->callGeminiDirect($message, $history, $userName, $userRole, $geminiRow['api_key']);
                    if (!empty($directResponse['success'])) {
                        return $this->json($directResponse);
                    }
                }
            } catch (Throwable $e) {}
        }

        // 7. Fallback Cerdas Database PHP jika seluruh API timeout/down
        $phpDbReply = $this->getDirectDatabaseFallbackResponse($message, $userRole, $userName, $history);
        return $this->json([
            'success' => true,
            'reply' => $phpDbReply,
            'model' => 'FaceAbsen DB Engine (PHP Offline Fallback)',
            'engine' => 'Local Database Fallback'
        ]);
    }

    /**
     * Endpoint: GET /chat/health
     */
    public function health() {
        $res = $this->callNodeService('/api/health', null, 'GET', 3);
        if ($res !== false) {
            $data = json_decode($res, true);
            $data['proxy'] = 'PHP ChatController -> Node.js Active';
            return $this->json($data);
        }

        return $this->json([
            'status' => 'standby',
            'proxy' => 'PHP ChatController -> Standby / Direct Fallback Active',
            'model' => $this->nvidiaModel,
            'database_access' => 'enabled (read-only)',
            'coding_allowed' => false,
            'message' => 'Node.js service belum aktif atau dalam inisialisasi.'
        ]);
    }

    /**
     * Helper HTTP Call ke Node.js Server
     */
    private function callNodeService($path, $postData = null, $method = 'POST', $timeout = 25) {
        $url = $this->nodeServerUrl . $path;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($postData)
            ]);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300 && !empty($response)) {
            return $response;
        }

        return false;
    }

    /**
     * Ambil data ringkasan peserta didik, guru, dan analitik presensi hari ini dari database (Read-Only)
     */
    private function getSchoolDatabaseContext($isSuperUser = false) {
        try {
            $db = new Database();
            
            // Baca Peserta Didik
            $db->query("
                SELECT s.nis, s.phone, u.name, u.email, c.name as class_name, c.level,
                       IF(s.face_model_identifier IS NOT NULL AND s.face_model_identifier != '', 'Terdaftar', 'Belum Terdaftar') as status_wajah
                FROM students s
                JOIN users u ON s.user_id = u.id
                JOIN classes c ON s.class_id = c.id
                WHERE u.status = 'active'
                ORDER BY c.name ASC, u.name ASC
            ");
            $students = $db->resultSet();

            // Baca Guru
            $db->query("
                SELECT t.nip, t.phone, u.name, u.email, u.status
                FROM teachers t
                JOIN users u ON t.user_id = u.id
                WHERE u.status = 'active'
                ORDER BY u.name ASC
            ");
            $teachers = $db->resultSet();

            if ($isSuperUser) {
                // 1. Data Rombel / Kelas
                $db->query("
                    SELECT c.id, c.name as class_name, c.level, COUNT(s.id) as total_students
                    FROM classes c
                    LEFT JOIN students s ON s.class_id = c.id
                    GROUP BY c.id, c.name, c.level
                    ORDER BY c.level ASC, c.name ASC
                ");
                $classes = $db->resultSet();

                // 2. Data Pengaturan Sistem (Settings)
                $db->query("SELECT setting_key, setting_value FROM settings");
                $settings = $db->resultSet();

                // 3. Data Pengguna / Akun (Users - Tanpa Password)
                $db->query("
                    SELECT id, username, name, role, status,
                           DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') as created_at
                    FROM users
                    ORDER BY FIELD(role, 'super_user', 'guru', 'peserta_didik'), name ASC
                ");
                $users = $db->resultSet();

                // 4. Rekapitulasi Presensi Hari Ini
                $db->query("
                    SELECT
                        COUNT(*) as total_records,
                        COALESCE(SUM(CASE WHEN a.status = 'hadir' THEN 1 ELSE 0 END), 0) as hadir,
                        COALESCE(SUM(CASE WHEN a.status = 'terlambat' THEN 1 ELSE 0 END), 0) as terlambat,
                        COALESCE(SUM(CASE WHEN u.role = 'peserta_didik' AND a.status IN ('hadir', 'terlambat') THEN 1 ELSE 0 END), 0) as siswa_hadir,
                        COALESCE(SUM(CASE WHEN u.role = 'guru' AND a.status IN ('hadir', 'terlambat') THEN 1 ELSE 0 END), 0) as guru_hadir
                    FROM attendances a
                    JOIN users u ON a.user_id = u.id
                    WHERE a.attendance_date = CURRENT_DATE()
                ");
                $daily = $db->single() ?: ['total_records' => 0, 'hadir' => 0, 'terlambat' => 0, 'siswa_hadir' => 0, 'guru_hadir' => 0];

                // 5. Analitik Per Kelas
                $db->query("
                    SELECT c.id, c.name as class_name, c.level,
                           COUNT(s.id) as total_students,
                           COALESCE(SUM(CASE WHEN a.status = 'hadir' THEN 1 ELSE 0 END), 0) as hadir,
                           COALESCE(SUM(CASE WHEN a.status = 'terlambat' THEN 1 ELSE 0 END), 0) as terlambat
                    FROM classes c
                    LEFT JOIN students s ON s.class_id = c.id
                    LEFT JOIN attendances a ON a.user_id = s.user_id AND a.attendance_date = CURRENT_DATE()
                    GROUP BY c.id, c.name, c.level
                    ORDER BY c.level ASC, c.name ASC
                ");
                $classSummary = $db->resultSet();

                // 6. Riwayat Presensi Terbaru
                $db->query("
                    SELECT a.id, u.name, u.role,
                           DATE_FORMAT(a.attendance_date, '%Y-%m-%d') as attendance_date,
                           a.attendance_time, a.status, a.method, a.confidence_score
                    FROM attendances a
                    JOIN users u ON a.user_id = u.id
                    ORDER BY a.attendance_date DESC, a.attendance_time DESC
                    LIMIT 15
                ");
                $recentAttendances = $db->resultSet();

                $totStudents = count($students);
                $totTeachers = count($teachers);
                $totalHadirSekolah = (int)($daily['hadir'] ?? 0);
                $totalTerlambatSekolah = (int)($daily['terlambat'] ?? 0);
                $totalSudahAbsen = $totalHadirSekolah + $totalTerlambatSekolah;
                $siswaHadir = (int)($daily['siswa_hadir'] ?? 0);
                $guruHadir = (int)($daily['guru_hadir'] ?? 0);
                $siswaBelumAbsen = max(0, $totStudents - $siswaHadir);
                $guruBelumAbsen = max(0, $totTeachers - $guruHadir);
                $persentaseSiswa = $totStudents > 0 ? round(($siswaHadir / $totStudents) * 100) : 0;
                $persentaseGuru = $totTeachers > 0 ? round(($guruHadir / $totTeachers) * 100) : 0;

                $classAnalyticsRows = '';
                foreach ($classSummary as $c) {
                    $cTot = (int)($c['total_students'] ?? 0);
                    $cHadir = (int)($c['hadir'] ?? 0);
                    $cTelat = (int)($c['terlambat'] ?? 0);
                    $cMasuk = $cHadir + $cTelat;
                    $cBelum = max(0, $cTot - $cMasuk);
                    $cPct = $cTot > 0 ? round(($cMasuk / $cTot) * 100) : 0;
                    $classAnalyticsRows .= "- Kelas {$c['class_name']} (Tingkat {$c['level']}): Total {$cTot} siswa | Hadir: {$cHadir} | Terlambat: {$cTelat} | Belum: {$cBelum} | Persentase: {$cPct}%\n";
                }

                $studentRows = '';
                foreach ($students as $s) {
                    $phoneStr = !empty($s['phone']) ? " | Telp: {$s['phone']}" : "";
                    $studentRows .= "- {$s['name']} | NIS: {$s['nis']} | Kelas: {$s['class_name']} (Tingkat {$s['level']}){$phoneStr} | Status Wajah AI: {$s['status_wajah']}\n";
                }

                $teacherRows = '';
                foreach ($teachers as $t) {
                    $phoneStr = !empty($t['phone']) ? " | Telp: {$t['phone']}" : "";
                    $teacherRows .= "- {$t['name']} | NIP: {$t['nip']}{$phoneStr}\n";
                }

                $classMasterRows = '';
                foreach ($classes as $c) {
                    $classMasterRows .= "- {$c['class_name']} (Tingkat {$c['level']}) | ID: {$c['id']} | Jumlah Siswa: {$c['total_students']}\n";
                }

                $settingRows = '';
                foreach ($settings as $st) {
                    $settingRows .= "- {$st['setting_key']}: {$st['setting_value']}\n";
                }

                $userRows = '';
                foreach ($users as $u) {
                    $userRows .= "- [" . strtoupper($u['role']) . "] {$u['name']} (username: {$u['username']}) | Status: {$u['status']} | Terdaftar: {$u['created_at']}\n";
                }

                $recentAttendanceRows = '';
                if (!empty($recentAttendances)) {
                    foreach ($recentAttendances as $a) {
                        $recentAttendanceRows .= "- [{$a['attendance_date']} {$a['attendance_time']}] {$a['name']} ({$a['role']}) | Status: {$a['status']} | Metode: {$a['method']} | Akurasi: " . ($a['confidence_score'] ?? '-') . "%\n";
                    }
                } else {
                    $recentAttendanceRows = "- Belum ada data presensi yang tercatat.\n";
                }

                $broadcastRows = '';
                try {
                    $db->query("
                        SELECT id, recipient_type, target_names, total_recipients, message, status,
                               DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') as created_at
                        FROM broadcast_messages
                        ORDER BY id DESC LIMIT 10
                    ");
                    $broadcastMessages = $db->resultSet();
                    if (!empty($broadcastMessages)) {
                        foreach ($broadcastMessages as $b) {
                            $broadcastRows .= "- [ID: {$b['id']} | {$b['created_at']}] Ke: {$b['target_names']} ({$b['recipient_type']}) | Penerima: {$b['total_recipients']} orang | Status: {$b['status']} | Pesan: \"{$b['message']}\"\n";
                        }
                    } else {
                        $broadcastRows = "- Belum ada log pesan WhatsApp.\n";
                    }
                } catch (Throwable $e) {
                    $broadcastRows = "- Belum ada log pesan WhatsApp.\n";
                }

                return "
SELURUH DATA 7 TABEL DATABASE FACEABSEN (HAK AKSES RESMI SUPER USER):

1. TABEL 'attendances' (Presensi, Analitik Kehadiran & Riwayat Log):
   - Total Terdaftar: {$totStudents} Siswa, {$totTeachers} Guru
   - Presensi Tercatat: {$totalSudahAbsen} orang (Tepat Waktu: {$totalHadirSekolah}, Terlambat: {$totalTerlambatSekolah})
   - Kehadiran Siswa: {$siswaHadir} dari {$totStudents} siswa ({$persentaseSiswa}% hadir, {$siswaBelumAbsen} belum absen)
   - Kehadiran Guru: {$guruHadir} dari {$totTeachers} guru ({$persentaseGuru}% hadir, {$guruBelumAbsen} belum absen)
   - Log Presensi Terbaru:
{$recentAttendanceRows}

2. TABEL 'broadcast_messages' (Riwayat Pesan WhatsApp Gateway Fonnte):
{$broadcastRows}

3. TABEL 'classes' (Master Rombel / Kelas - Total: " . count($classes) . " Kelas):
{$classMasterRows}
   - Rincian Kehadiran Per Rombel:
{$classAnalyticsRows}

4. TABEL 'settings' (Pengaturan Sistem Presensi - Total: " . count($settings) . " Konfigurasi):
{$settingRows}

5. TABEL 'students' (Master Peserta Didik - Total: {$totStudents} Siswa):
{$studentRows}

6. TABEL 'teachers' (Master Tenaga Pengajar / Guru - Total: {$totTeachers} Guru):
{$teacherRows}

7. TABEL 'users' (Master Akun Pengguna - Total: " . count($users) . " Akun):
{$userRows}
";
            }

            $studentText = '';
            foreach ($students as $s) {
                $phoneStr = !empty($s['phone']) ? " | Telp: {$s['phone']}" : "";
                $studentText .= "- {$s['name']} | NIS: {$s['nis']} | Kelas: {$s['class_name']}{$phoneStr} | Wajah AI: {$s['status_wajah']}\n";
            }

            $teacherText = '';
            foreach ($teachers as $t) {
                $phoneStr = !empty($t['phone']) ? " | Telp: {$t['phone']}" : "";
                $teacherText .= "- {$t['name']} | NIP: {$t['nip']}{$phoneStr}\n";
            }

            $totalSiswa = count($students);
            $totalGuru = count($teachers);

            return "
DATA RESMI DATABASE FACEABSEN (HANYA BACA):
Data Peserta Didik ({$totalSiswa} orang):
{$studentText}
Data Tenaga Pengajar / Guru ({$totalGuru} orang):
{$teacherText}
";
        } catch (Throwable $e) {
            return '';
        }
    }

    /**
     * Helper Fallback Langsung ke NVIDIA NIM API
     */
    private function callNvidiaDirect($message, $history, $userName, $userRole, $apiKey = null) {
        $isSuperUser = stripos($userRole, 'super_user') !== false || stripos($userRole, 'admin') !== false;
        $isGuru = stripos($userRole, 'guru') !== false;
        $roleLabel = $isSuperUser ? 'Super User / Administrator' : ($isGuru ? 'Guru / Tenaga Pendidik' : 'Peserta Didik (Siswa)');

        $dbContext = $this->getSchoolDatabaseContext($isSuperUser);

        if ($isSuperUser) {
            $systemPrompt = "Kamu adalah FaceAbsen AI, asisten analitik, manajemen, dan informasi seluruh database sistem presensi sekolah untuk Super User / Administrator.
Pengguna: {$userName} (Super User / Administrator).

HAK AKSES DATA DATABASE SUPER USER:
Super User / Administrator memiliki izin membaca SELURUH 7 TABEL DATABASE FaceAbsen yang tersedia:
1. Tabel 'attendances': Rekapitulasi presensi, analitik per rombel, dan riwayat log kehadiran real-time.
2. Tabel 'broadcast_messages': Riwayat pengiriman pesan WhatsApp ke guru dan siswa via gateway Fonnte.
3. Tabel 'classes': Master data rombongan belajar/kelas, tingkat rombel, dan jumlah siswa.
4. Tabel 'settings': Konfigurasi pengaturan sistem presensi (jam masuk, batas terlambat, jam pulang, toleransi akurasi wajah AI, URL model Teachable Machine, nama sekolah, fonnte_api_key).
5. Tabel 'students': Data peserta didik (nama, NIS, nomor telepon, kelas, tingkat, status pendaftaran wajah AI).
6. Tabel 'teachers': Data tenaga pengajar / guru (nama, NIP, nomor telepon, status keaktifan).
7. Tabel 'users': Data akun pengguna sistem (username, nama lengkap, role/peran, status akun).

ATURAN PALING UTAMA & KETAT:
1. DILARANG KERAS MELAKUKAN CODING DALAM BENTUK APAPUN!
   - Jangan pernah menulis, membuat, memberikan, menjelaskan, atau membahas kode program dalam bahasa apapun.
   - Jika pengguna meminta coding, tolak langsung: '" . self::CODING_REFUSAL_MESSAGE . "'
2. JAWAB HANYA SESUAI PERTANYAAN DAN JANGAN MEMBUAT KOMENTAR APAPUN:
   - Jawab langsung ke inti apa yang ditanyakan secara to-the-point dan faktual dari data database.
   - Jangan menampilkan data atau kategori lain yang tidak ditanyakan.
   - DILARANG MEMBUAT KOMENTAR, OPINI, EVALUASI, ATAU SARAN APAPUN (jangan mengomentari seperti 'kehadiran baik', 'perlu ditingkatkan', dll).
   - DILARANG MEMBUAT BASA-BASI, SALAM, MAUPUN KALIMAT PEMBUKA/PENUTUP (jangan gunakan 'Halo', 'Baik', 'Tentu', 'Berikut data...', 'Semoga membantu', 'Silakan tanyakan lagi', dll). Langsung sajikan data/jawaban.
3. BATASAN TOPIK: HANYA SEPUTAR SISTEM FACEABSEN DAN SELURUH DATA DATABASE-NYA.
   - Selain sistem FaceAbsen dan data di dalamnya, tolak langsung: 'Maaf, saya hanya dapat membantu seputar sistem dan seluruh data FaceAbsen.'
4. ATURAN IDENTITAS:
   - Jika pengguna menanyakan 'siapa saya' atau variasinya: Jawab dengan menyebut nama pengguna dan jabatannya: 'Anda adalah {$userName}, dengan jabatan sebagai Super User (Administrator).'
   - Jika pengguna menanyakan 'siapa anda' atau variasinya: Jawab dengan menyebut diri chatbot: 'Saya adalah FaceAbsen AI, asisten virtual untuk sistem presensi sekolah FaceAbsen.'
5. ATURAN JAWABAN ANGKA vs DAFTAR NAMA (SUPER USER):
   - Jika pengguna menanyakan JUMLAH / 'BERAPA BANYAK' / 'ADA BERAPA':
     Jawab HANYA angka totalnya saja secara ringkas tanpa menyebutkan daftar nama!
     * Contoh: 'ada berapa banyak siswa yang ada di sekolah ini' → Jawab angka jumlah siswa: 'Total siswa: X orang.'
     * Contoh: 'ada berapa banyak yang hadir hari ini' → Jawab angka yang hadir (guru & siswa): 'Total hadir hari ini: Z orang (Siswa: X, Guru: Y).'
     * Contoh: 'ada berapa banyak guru' → Jawab angka jumlah guru: 'Total guru: X orang.'
     * Contoh: 'ada berapa banyak kelas' → Jawab angka jumlah kelas: 'Total kelas: X rombel.'
   - Jika pengguna menanyakan DAFTAR / SEBUTKAN 'NAMA NYA' / 'SIAPA SAJA':
     Sajikan daftar nama-namanya sesuai data database!
     * Contoh: 'siapa saja yang hadir nya' / 'sebutkan nama yang hadir' → Sebutkan nama-nama yang sudah hadir hari ini (jika belum ada, jawab: 'Belum ada yang hadir hari ini.').
     * Contoh: 'sebutkan nama siswanya' / 'siapa saja siswanya' → Sebutkan daftar nama siswa.
     * Contoh: 'sebutkan nama gurunya' / 'siapa saja gurunya' → Sebutkan daftar nama guru.

{$dbContext}";
        } else {
            $systemPrompt = "Kamu adalah FaceAbsen AI, asisten virtual untuk sistem presensi sekolah berbasis AI (Teachable Machine Face Recognition).
Pengguna: {$userName} ({$roleLabel}).

ATURAN PALING UTAMA & KETAT:
1. DILARANG KERAS MELAKUKAN CODING!
   - Tolak langsung jika meminta coding: '" . self::CODING_REFUSAL_MESSAGE . "'
2. JAWAB HANYA SESUAI PERTANYAAN DAN JANGAN MEMBUAT KOMENTAR APAPUN:
   - Jawab langsung ke inti pertanyaan tanpa basa-basi, salam, kalimat pembuka, kalimat penutup, komentar, opini, evaluasi, atau saran yang tidak diminta.
   - Jangan memberikan informasi di luar yang ditanyakan.
3. TOPIK YANG BOLEH DIJAWAB (HANYA INI SAJA):
   a. Data Peserta Didik (Siswa): Membaca nama, NIS, kelas, dan status model wajah berdasarkan DATA RESMI DATABASE di bawah.
   b. Data Tenaga Pengajar (Guru): Membaca nama guru dan NIP berdasarkan DATA RESMI DATABASE di bawah.
   c. Sistem presensi FaceAbsen:
      - Jam presensi: Masuk mulai 06:30 WIB, batas tepat waktu 07:15 WIB (lewat = Terlambat), pulang 16:00 WIB.
      - Cara presensi wajah: Klik 'Buka FaceAbsen' → izinkan kamera → posisikan wajah di bingkai oval → sistem AI mencatat otomatis.
      - Kendala kamera/wajah: Cek izin kamera di browser, pastikan pencahayaan cukup dan wajah tidak terhalang.
      - Keterangan tidak hadir (sakit/izin): Lapor ke wali kelas atau admin presensi.
      - Riwayat absensi & rekap kehadiran rombel guru.
4. SELAIN TOPIK DI ATAS JANGAN BAHAS APAPUN. Tolak dengan: 'Maaf, saya hanya bisa membantu seputar sistem presensi FaceAbsen serta data peserta didik dan tenaga pengajar.'
5. ATURAN IDENTITAS:
   - Jika pengguna menanyakan 'siapa saya' atau variasinya: Jawab dengan menyebut nama pengguna dan jabatannya: 'Anda adalah {$userName}, dengan jabatan sebagai {$roleLabel}.'
   - Jika pengguna menanyakan 'siapa anda' atau variasinya: Jawab dengan menyebut diri chatbot: 'Saya adalah FaceAbsen AI, asisten virtual untuk sistem presensi sekolah FaceAbsen.'
{$dbContext}";
        }

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt]
        ];

        foreach (array_slice($history, -4) as $h) {
            if (!empty($h['role']) && !empty($h['content'])) {
                $messages[] = [
                    'role' => $h['role'],
                    'content' => $h['content']
                ];
            }
        }

        $messages[] = [
            'role' => 'user',
            'content' => $message
        ];

        $modelsToTry = [$this->nvidiaModel, $this->nvidiaFallbackModel];
        $lastError = '';
        $keyToUse = $apiKey ?: $this->getActiveNvidiaApiKey();

        foreach ($modelsToTry as $model) {
            $payload = json_encode([
                'model' => $model,
                'messages' => $messages,
                'max_tokens' => 450,
                'temperature' => 0.3
            ]);

            $timeout = ($model === $this->nvidiaModel) ? 12 : 30;

            $ch = curl_init('https://integrate.api.nvidia.com/v1/chat/completions');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $keyToUse,
                'Content-Type: application/json',
                'Content-Length: ' . strlen($payload)
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr = curl_error($ch);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300 && !empty($response)) {
                $parsed = json_decode($response, true);
                $reply = $parsed['choices'][0]['message']['content'] ?? 'Maaf, tidak ada balasan.';
                if (strpos($reply, '```') !== false) {
                    $reply = self::CODING_REFUSAL_MESSAGE;
                }
                return [
                    'success' => true,
                    'reply' => $reply,
                    'model' => $model,
                    'engine' => 'Direct NVIDIA NIM API (Fallback)'
                ];
            }

            $lastError = $curlErr ?: "HTTP Status {$httpCode}";
        }

        return [
            'success' => false,
            'error' => 'Gagal memproses AI: ' . $lastError
        ];
    }

    /**
     * Helper Fallback Langsung ke Google Gemini API
     */
    private function callGeminiDirect($message, $history, $userName, $userRole, $apiKey) {
        $isSuperUser = stripos($userRole, 'super_user') !== false || stripos($userRole, 'admin') !== false;
        $isGuru = stripos($userRole, 'guru') !== false;
        $roleLabel = $isSuperUser ? 'Super User / Administrator' : ($isGuru ? 'Guru / Tenaga Pendidik' : 'Peserta Didik (Siswa)');

        $dbContext = $this->getSchoolDatabaseContext($isSuperUser);

        if ($isSuperUser) {
            $systemPrompt = "Kamu adalah FaceAbsen AI, asisten analitik, manajemen, dan informasi seluruh database sistem presensi sekolah untuk Super User / Administrator.
Pengguna: {$userName} (Super User / Administrator).

HAK AKSES DATA DATABASE SUPER USER:
Super User / Administrator memiliki izin membaca SELURUH 7 TABEL DATABASE FaceAbsen yang tersedia:
1. Tabel 'attendances': Rekapitulasi presensi, analitik per rombel, dan riwayat log kehadiran real-time.
2. Tabel 'broadcast_messages': Riwayat pengiriman pesan WhatsApp ke guru dan siswa via gateway Fonnte.
3. Tabel 'classes': Master data rombongan belajar/kelas, tingkat rombel, dan jumlah siswa.
4. Tabel 'settings': Konfigurasi pengaturan sistem presensi (jam masuk, batas terlambat, jam pulang, toleransi akurasi wajah AI, URL model Teachable Machine, nama sekolah, fonnte_api_key).
5. Tabel 'students': Data peserta didik (nama, NIS, nomor telepon, kelas, tingkat, status pendaftaran wajah AI).
6. Tabel 'teachers': Data tenaga pengajar / guru (nama, NIP, nomor telepon, status keaktifan).
7. Tabel 'users': Data akun pengguna sistem (username, nama lengkap, role/peran, status akun).

ATURAN PALING UTAMA & KETAT:
1. DILARANG KERAS MELAKUKAN CODING DALAM BENTUK APAPUN!
   - Jangan pernah menulis, membuat, memberikan, menjelaskan, atau membahas kode program dalam bahasa apapun.
   - Jika pengguna meminta coding, tolak langsung: '" . self::CODING_REFUSAL_MESSAGE . "'
2. JAWAB HANYA SESUAI PERTANYAAN DAN JANGAN MEMBUAT KOMENTAR APAPUN:
   - Jawab langsung ke inti apa yang ditanyakan secara to-the-point dan faktual dari data database.
   - Jangan menampilkan data atau kategori lain yang tidak ditanyakan.
   - DILARANG MEMBUAT KOMENTAR, OPINI, EVALUASI, ATAU SARAN APAPUN. Langsung sajikan data/jawaban.
3. BATASAN TOPIK: HANYA SEPUTAR SISTEM FACEABSEN DAN SELURUH DATA DATABASE-NYA.
4. ATURAN IDENTITAS:
   - Jika pengguna menanyakan 'siapa saya' atau variasinya: Jawab dengan menyebut nama pengguna dan jabatannya: 'Anda adalah {$userName}, dengan jabatan sebagai Super User (Administrator).'
   - Jika pengguna menanyakan 'siapa anda' atau variasinya: Jawab dengan menyebut diri chatbot: 'Saya adalah FaceAbsen AI, asisten virtual untuk sistem presensi sekolah FaceAbsen.'
5. ATURAN JAWABAN ANGKA vs DAFTAR NAMA:
   - Jika pengguna menanyakan JUMLAH / 'BERAPA BANYAK': Jawab HANYA angka totalnya saja secara ringkas tanpa menyebutkan daftar nama!
   - Jika pengguna menanyakan DAFTAR / SEBUTKAN 'NAMA NYA' / 'SIAPA SAJA': Sajikan daftar nama-namanya sesuai data database!

{$dbContext}";
        } else {
            $systemPrompt = "Kamu adalah FaceAbsen AI, asisten virtual untuk sistem presensi sekolah berbasis AI (Teachable Machine Face Recognition).
Pengguna: {$userName} ({$roleLabel}).

ATURAN PALING UTAMA & KETAT:
1. DILARANG KERAS MELAKUKAN CODING!
   - Tolak langsung jika meminta coding: '" . self::CODING_REFUSAL_MESSAGE . "'
2. JAWAB HANYA SESUAI PERTANYAAN DAN JANGAN MEMBUAT KOMENTAR APAPUN:
   - Jawab langsung ke inti pertanyaan tanpa basa-basi, komentar, opini, atau saran yang tidak diminta.
3. TOPIK YANG BOLEH DIJAWAB (HANYA INI SAJA):
   a. Data Peserta Didik (Siswa): Membaca nama, NIS, kelas, dan status model wajah berdasarkan DATA RESMI DATABASE di bawah.
   b. Data Tenaga Pengajar (Guru): Membaca nama guru dan NIP berdasarkan DATA RESMI DATABASE di bawah.
   c. Sistem presensi FaceAbsen:
      - Jam presensi: Masuk mulai 06:30 WIB, batas tepat waktu 07:15 WIB (lewat = Terlambat), pulang 16:00 WIB.
      - Cara presensi wajah: Klik 'Buka FaceAbsen' -> izinkan kamera -> posisikan wajah di bingkai oval -> sistem AI mencatat otomatis.
      - Kendala kamera/wajah: Cek izin kamera di browser, pastikan pencahayaan cukup dan wajah tidak terhalang.
      - Keterangan tidak hadir (sakit/izin): Lapor ke wali kelas atau admin presensi.
      - Riwayat absensi & rekap kehadiran rombel guru.
4. SELAIN TOPIK DI ATAS JANGAN BAHAS APAPUN. Tolak dengan: 'Maaf, saya hanya bisa membantu seputar sistem presensi FaceAbsen serta data peserta didik dan tenaga pengajar.'
5. ATURAN IDENTITAS:
   - Jika pengguna menanyakan 'siapa saya' atau variasinya: Jawab: 'Anda adalah {$userName}, dengan jabatan sebagai {$roleLabel}.'
   - Jika pengguna menanyakan 'siapa anda' atau variasinya: Jawab: 'Saya adalah FaceAbsen AI, asisten virtual untuk sistem presensi sekolah FaceAbsen.'

{$dbContext}";
        }

        $contents = [];
        foreach (array_slice($history, -4) as $h) {
            if (!empty($h['role']) && !empty($h['content'])) {
                $contents[] = [
                    'role' => ($h['role'] === 'user') ? 'user' : 'model',
                    'parts' => [['text' => (string)$h['content']]]
                ];
            }
        }
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => (string)$message]]
        ];

        $payload = json_encode([
            'system_instruction' => [
                'parts' => [['text' => $systemPrompt]]
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.3,
                'maxOutputTokens' => 1200
            ]
        ]);

        $modelName = 'gemini-3.5-flash';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent?key=" . urlencode($apiKey);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_TIMEOUT, 45);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload)
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300 && !empty($response)) {
            $parsed = json_decode($response, true);
            $reply = $parsed['candidates'][0]['content']['parts'][0]['text'] ?? 'Maaf, tidak ada balasan.';
            if (strpos($reply, '```') !== false) {
                $reply = self::CODING_REFUSAL_MESSAGE;
            }
            return [
                'success' => true,
                'reply' => $reply,
                'model' => "Google Gemini ({$modelName})",
                'engine' => 'Direct Google Gemini API (Fallback)'
            ];
        }

        return [
            'success' => false,
            'error' => $curlErr ?: "Gemini HTTP Status {$httpCode}"
        ];
    }

    /**
     * Fallback cerdas: Menjawab langsung dari database lokal PHP jika AI eksternal offline/timeout
     */
    private function getDirectDatabaseFallbackResponse($message, $userRole, $userName, $history = []) {
        // Cek pertanyaan identitas terlebih dahulu
        $identityReply = $this->getIdentityResponse($message, $userName, $userRole);
        if ($identityReply !== null) {
            return $identityReply;
        }

        $isSuperUser = strpos($userRole, 'super_user') !== false || strpos($userRole, 'admin') !== false;

        // Cek pertanyaan khusus super user (jumlah vs nama)
        if ($isSuperUser) {
            $suReply = $this->handleSuperUserQuery($message, $history);
            if ($suReply !== null) {
                return $suReply;
            }
        }

        $lower = strtolower($message ?? '');

        try {
            $db = new Database();

            // 1. Seluruh Tabel yang Ada di Database
            if (strpos($lower, 'seluruh tabel') !== false || strpos($lower, 'semua tabel') !== false || strpos($lower, 'daftar tabel') !== false || (strpos($lower, 'tabel') !== false && (strpos($lower, 'apa') !== false || strpos($lower, 'database') !== false))) {
                $db->query("SELECT COUNT(*) as tot FROM students");
                $sTot = (int)($db->single()['tot'] ?? 0);
                $db->query("SELECT COUNT(*) as tot FROM teachers");
                $tTot = (int)($db->single()['tot'] ?? 0);
                $db->query("SELECT COUNT(*) as tot FROM classes");
                $cTot = (int)($db->single()['tot'] ?? 0);
                $db->query("SELECT COUNT(*) as tot FROM users");
                $uTot = (int)($db->single()['tot'] ?? 0);
                $db->query("SELECT COUNT(*) as tot FROM settings");
                $stTot = (int)($db->single()['tot'] ?? 0);
                $db->query("SELECT COUNT(*) as tot FROM attendances WHERE attendance_date = CURRENT_DATE()");
                $atTot = (int)($db->single()['tot'] ?? 0);
                $bmTot = 0;
                try {
                    $db->query("SELECT COUNT(*) as tot FROM broadcast_messages");
                    $bmTot = (int)($db->single()['tot'] ?? 0);
                } catch (Throwable $e) {}

                return "Tabel di database faceabsen_db (7 tabel):\n" .
                    "1. attendances ({$atTot} presensi hari ini)\n" .
                    "2. broadcast_messages ({$bmTot} pesan WhatsApp terkirim)\n" .
                    "3. classes ({$cTot} baris/kelas)\n" .
                    "4. settings ({$stTot} baris konfigurasi)\n" .
                    "5. students ({$sTot} baris/siswa)\n" .
                    "6. teachers ({$tTot} baris/guru)\n" .
                    "7. users ({$uTot} baris/akun)";
            }

            // 2. Pengaturan Sistem Presensi / Settings (Prioritas agar tidak tertimpa kata 'presensi')
            if (strpos($lower, 'pengaturan') !== false || strpos($lower, 'setting') !== false || strpos($lower, 'jadwal') !== false || strpos($lower, 'jam') !== false || strpos($lower, 'waktu') !== false || strpos($lower, 'teachable') !== false || strpos($lower, 'model') !== false || strpos($lower, 'akurasi') !== false || strpos($lower, 'threshold') !== false) {
                $db->query("SELECT setting_key, setting_value FROM settings");
                $settings = $db->resultSet();
                $lines = [];
                foreach ($settings as $s) {
                    $lines[] = "- {$s['setting_key']}: {$s['setting_value']}";
                }
                return implode("\n", $lines);
            }

            // 2. Rekapitulasi Presensi
            if (strpos($lower, 'rekap') !== false || strpos($lower, 'kehadiran') !== false || strpos($lower, 'absen') !== false || strpos($lower, 'presensi') !== false || strpos($lower, 'hadir') !== false || strpos($lower, 'terlambat') !== false) {
                $db->query("SELECT COUNT(*) as tot FROM students s JOIN users u ON s.user_id = u.id WHERE u.status = 'active'");
                $totStudents = (int)($db->single()['tot'] ?? 0);

                $db->query("SELECT COUNT(*) as tot FROM teachers t JOIN users u ON t.user_id = u.id WHERE u.status = 'active'");
                $totTeachers = (int)($db->single()['tot'] ?? 0);

                $db->query("
                    SELECT
                        COUNT(*) as total_records,
                        COALESCE(SUM(CASE WHEN a.status = 'hadir' THEN 1 ELSE 0 END), 0) as hadir,
                        COALESCE(SUM(CASE WHEN a.status = 'terlambat' THEN 1 ELSE 0 END), 0) as terlambat,
                        COALESCE(SUM(CASE WHEN u.role = 'peserta_didik' AND a.status IN ('hadir', 'terlambat') THEN 1 ELSE 0 END), 0) as siswa_hadir,
                        COALESCE(SUM(CASE WHEN u.role = 'guru' AND a.status IN ('hadir', 'terlambat') THEN 1 ELSE 0 END), 0) as guru_hadir
                    FROM attendances a
                    JOIN users u ON a.user_id = u.id
                    WHERE a.attendance_date = CURRENT_DATE()
                ");
                $daily = $db->single() ?: ['total_records' => 0, 'hadir' => 0, 'terlambat' => 0, 'siswa_hadir' => 0, 'guru_hadir' => 0];

                $hadir = (int)($daily['hadir'] ?? 0);
                $telat = (int)($daily['terlambat'] ?? 0);
                $totalMasuk = $hadir + $telat;
                $siswaHadir = (int)($daily['siswa_hadir'] ?? 0);
                $guruHadir = (int)($daily['guru_hadir'] ?? 0);
                $siswaBelum = max(0, $totStudents - $siswaHadir);
                $guruBelum = max(0, $totTeachers - guruHadir);
                $pctSiswa = $totStudents > 0 ? round(($siswaHadir / $totStudents) * 100) : 0;
                $pctGuru = $totTeachers > 0 ? round(($guruHadir / $totTeachers) * 100) : 0;

                return "- Total Terdaftar: {$totStudents} Siswa, {$totTeachers} Guru\n" .
                    "- Presensi Tercatat: {$totalMasuk} orang ({$hadir} Tepat Waktu, {$telat} Terlambat)\n" .
                    "- Kehadiran Siswa: {$siswaHadir}/{$totStudents} ({$pctSiswa}%, {$siswaBelum} belum absen)\n" .
                    "- Kehadiran Guru: {$guruHadir}/{$totTeachers} ({$pctGuru}%, {$guruBelum} belum absen)";
            }

            // 3. Analitik Per Rombel / Kelas
            if (strpos($lower, 'rombel') !== false || strpos($lower, 'kelas') !== false || strpos($lower, 'tingkat') !== false || strpos($lower, 'analitik') !== false) {
                $db->query("
                    SELECT c.name as class_name, c.level,
                           COUNT(s.id) as total_students,
                           COALESCE(SUM(CASE WHEN a.status = 'hadir' THEN 1 ELSE 0 END), 0) as hadir,
                           COALESCE(SUM(CASE WHEN a.status = 'terlambat' THEN 1 ELSE 0 END), 0) as terlambat
                    FROM classes c
                    LEFT JOIN students s ON s.class_id = c.id
                    LEFT JOIN attendances a ON a.user_id = s.user_id AND a.attendance_date = CURRENT_DATE()
                    GROUP BY c.id, c.name, c.level
                    ORDER BY c.level ASC, c.name ASC
                ");
                $rows = $db->resultSet();
                $lines = [];
                foreach ($rows as $c) {
                    $tot = (int)($c['total_students'] ?? 0);
                    $h = (int)($c['hadir'] ?? 0);
                    $t = (int)($c['terlambat'] ?? 0);
                    $masuk = $h + $t;
                    $blm = max(0, $tot - masuk);
                    $pct = $tot > 0 ? round(($masuk / $tot) * 100) : 0;
                    $lines[] = "- Kelas {$c['class_name']} (Tingkat {$c['level']}): Total {$tot} siswa | Hadir: {$h} | Terlambat: {$t} | Belum: {$blm} | Kehadiran: {$pct}%";
                }
                return implode("\n", $lines);
            }

            // 4. Data Peserta Didik
            if (strpos($lower, 'siswa') !== false || strpos($lower, 'peserta didik') !== false || strpos($lower, 'murid') !== false || strpos($lower, 'nis') !== false) {
                $db->query("
                    SELECT s.nis, s.phone, u.name, c.name as class_name, c.level,
                           IF(s.face_model_identifier IS NOT NULL AND s.face_model_identifier != '', 'Terdaftar', 'Belum Terdaftar') as status_wajah
                    FROM students s
                    JOIN users u ON s.user_id = u.id
                    JOIN classes c ON s.class_id = c.id
                    WHERE u.status = 'active'
                    ORDER BY c.level ASC, c.name ASC, u.name ASC
                ");
                $students = $db->resultSet();
                $lines = [];
                foreach ($students as $s) {
                    $phoneStr = !empty($s['phone']) ? " | Telp: {$s['phone']}" : "";
                    $lines[] = "- {$s['name']} (NIS: {$s['nis']}) | Kelas: {$s['class_name']} (Tingkat {$s['level']}){$phoneStr} | Status Wajah AI: {$s['status_wajah']}";
                }
                return implode("\n", $lines);
            }

            // 5. Data Tenaga Pengajar / Guru
            if (strpos($lower, 'guru') !== false || strpos($lower, 'pengajar') !== false || strpos($lower, 'pendidik') !== false || strpos($lower, 'nip') !== false) {
                $db->query("
                    SELECT t.nip, t.phone, u.name, u.status
                    FROM teachers t
                    JOIN users u ON t.user_id = u.id
                    WHERE u.status = 'active'
                    ORDER BY u.name ASC
                ");
                $teachers = $db->resultSet();
                $lines = [];
                foreach ($teachers as $t) {
                    $phoneStr = !empty($t['phone']) ? " | Telp: {$t['phone']}" : "";
                    $lines[] = "- {$t['name']} (NIP: {$t['nip']}){$phoneStr} | Status: {$t['status']}";
                }
                return implode("\n", $lines);
            }

            // 6. Data Pengguna / Akun
            if (strpos($lower, 'user') !== false || strpos($lower, 'pengguna') !== false || strpos($lower, 'akun') !== false || strpos($lower, 'role') !== false || strpos($lower, 'admin') !== false) {
                $db->query("
                    SELECT username, name, role, status
                    FROM users
                    ORDER BY FIELD(role, 'super_user', 'guru', 'peserta_didik'), name ASC
                ");
                $users = $db->resultSet();
                $lines = [];
                foreach ($users as $u) {
                    $roleUpper = strtoupper($u['role']);
                    $lines[] = "- [{$roleUpper}] {$u['name']} (username: {$u['username']}) | Status: {$u['status']}";
                }
                return implode("\n", $lines);
            }

            // 7. Log / Riwayat Presensi Terbaru
            if (strpos($lower, 'log') !== false || strpos($lower, 'riwayat') !== false || strpos($lower, 'history') !== false || strpos($lower, 'terbaru') !== false || strpos($lower, 'aktivitas') !== false) {
                $db->query("
                    SELECT u.name, u.role, DATE_FORMAT(a.attendance_date, '%Y-%m-%d') as attendance_date,
                           a.attendance_time, a.status, a.method, a.confidence_score
                    FROM attendances a
                    JOIN users u ON a.user_id = u.id
                    ORDER BY a.attendance_date DESC, a.attendance_time DESC
                    LIMIT 15
                ");
                $logs = $db->resultSet();
                if (empty($logs)) {
                    return "Belum ada catatan riwayat presensi.";
                }
                $lines = [];
                foreach ($logs as $a) {
                    $st = strtoupper($a['status']);
                    $score = $a['confidence_score'] ?? 0;
                    $lines[] = "- {$a['attendance_date']} {$a['attendance_time']} | {$a['name']} ({$a['role']}) | Status: {$st} | Metode: {$a['method']} ({$score}%)";
                }
                return implode("\n", $lines);
            }

            // 8. Seluruh Database / Ringkasan Master
            if ($isSuperUser) {
                $db->query("SELECT COUNT(*) as tot FROM students");
                $sTot = (int)($db->single()['tot'] ?? 0);
                $db->query("SELECT COUNT(*) as tot FROM teachers");
                $tTot = (int)($db->single()['tot'] ?? 0);
                $db->query("SELECT COUNT(*) as tot FROM classes");
                $cTot = (int)($db->single()['tot'] ?? 0);
                $db->query("SELECT COUNT(*) as tot FROM users");
                $uTot = (int)($db->single()['tot'] ?? 0);
                $db->query("SELECT COUNT(*) as tot FROM settings");
                $stTot = (int)($db->single()['tot'] ?? 0);
                $db->query("SELECT COUNT(*) as tot FROM attendances WHERE attendance_date = CURRENT_DATE()");
                $atTot = (int)($db->single()['tot'] ?? 0);

                return "1. Peserta Didik: {$sTot} siswa\n" .
                    "2. Tenaga Pengajar: {$tTot} guru\n" .
                    "3. Rombel / Kelas: {$cTot} kelas\n" .
                    "4. Akun Pengguna: {$uTot} akun\n" .
                    "5. Pengaturan Sistem: {$stTot} konfigurasi\n" .
                    "6. Rekap Presensi Hari Ini: {$atTot} presensi tercatat\n" .
                    "7. Log Riwayat Presensi: 15 log aktivitas presensi terbaru";
            }

            return "Sistem FaceAbsen mencatat data presensi, peserta didik, dan tenaga pengajar.";

        } catch (\Exception $e) {
            return "Terjadi kendala saat membaca data database: " . $e->getMessage();
        }
    }
}
