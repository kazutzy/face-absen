<?php
/**
 * FonnteService - Layanan Integrasi WhatsApp Gateway (Fonnte API)
 * Dokumentasi API: https://docs.fonnte.com/
 */
require_once __DIR__ . '/../core/Database.php';

class FonnteService {

    const DEFAULT_API_KEY = 'bdvECGY1WDwuYNDFUg8H';
    const API_BASE_URL = 'https://api.fonnte.com';

    /**
     * Dapatkan API Key Fonnte dari database atau default
     */
    public static function getApiKey() {
        try {
            $db = new Database();
            $db->query("SELECT setting_value FROM settings WHERE setting_key = 'fonnte_api_key' LIMIT 1");
            $row = $db->single();
            if ($row && !empty(trim($row['setting_value']))) {
                return trim($row['setting_value']);
            }
        } catch (Throwable $e) {
            // Fallback jika database belum termigrasi
        }
        return self::DEFAULT_API_KEY;
    }

    /**
     * Simpan / Perbarui API Key Fonnte
     */
    public static function saveApiKey($apiKey) {
        $apiKey = trim($apiKey);
        $db = new Database();
        $db->query("
            INSERT INTO settings (setting_key, setting_value, created_at, updated_at)
            VALUES ('fonnte_api_key', :val, NOW(), NOW())
            ON DUPLICATE KEY UPDATE setting_value = :val2, updated_at = NOW()
        ");
        $db->bind(':val', $apiKey);
        $db->bind(':val2', $apiKey);
        return $db->execute();
    }

    /**
     * Cek status perangkat Fonnte (konektivitas WhatsApp, kuota pesan, nama device)
     */
    public static function getDeviceStatus() {
        $apiKey = self::getApiKey();

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => self::API_BASE_URL . '/device',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . $apiKey
            ],
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return [
                'status' => false,
                'error' => 'Koneksi cURL gagal: ' . $curlError,
                'device_status' => 'disconnected'
            ];
        }

        $json = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'status' => false,
                'error' => 'Respon gateway tidak valid: ' . $response,
                'device_status' => 'disconnected'
            ];
        }

        return $json;
    }

    /**
     * Kirim Pesan WhatsApp ke Satu atau Banyak Nomor
     * 
     * @param string|array $targets Nomor tujuan (tunggal atau array atau koma-terpisah)
     * @param string $message Pesan teks WhatsApp
     * @param int $delay Jeda antar pesan dalam detik (rekomendasi 2 detik)
     * @return array [success => bool, data => array, error => string]
     */
    public static function sendMessage($targets, $message, $delay = 2) {
        $apiKey = self::getApiKey();

        // Normalisasi target ke string koma-terpisah
        if (is_array($targets)) {
            $formatted = [];
            foreach ($targets as $t) {
                $cleaned = self::formatPhone($t);
                if (!empty($cleaned)) {
                    $formatted[] = $cleaned;
                }
            }
            $targetString = implode(',', array_unique($formatted));
        } else {
            $parts = explode(',', (string)$targets);
            $formatted = [];
            foreach ($parts as $p) {
                $cleaned = self::formatPhone($p);
                if (!empty($cleaned)) {
                    $formatted[] = $cleaned;
                }
            }
            $targetString = implode(',', array_unique($formatted));
        }

        if (empty($targetString)) {
            return [
                'success' => false,
                'error' => 'Tidak ada nomor telepon tujuan yang valid.'
            ];
        }

        if (empty(trim($message))) {
            return [
                'success' => false,
                'error' => 'Isi pesan tidak boleh kosong.'
            ];
        }

        $postData = [
            'target' => $targetString,
            'message' => trim($message),
            'delay' => (string)$delay,
            'countryCode' => '62'
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => self::API_BASE_URL . '/send',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . $apiKey
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return [
                'success' => false,
                'error' => 'Gagal menghubungi server WhatsApp: ' . $curlError,
                'raw_response' => null
            ];
        }

        $json = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => 'Respon gateway tidak terduga: ' . $response,
                'raw_response' => $response
            ];
        }

        // Fonnte mengembalikan status: true jika request berhasil diproses
        $isSuccess = isset($json['status']) && $json['status'] === true;

        return [
            'success' => $isSuccess,
            'target' => $targetString,
            'data' => $json,
            'raw_response' => $response,
            'error' => $isSuccess ? null : ($json['reason'] ?? 'Gagal mengirim pesan melalui Fonnte.')
        ];
    }

    /**
     * Standardisasi format nomor telepon Indonesia
     * Contoh: 081234567890 -> 081234567890 (Fonnte menerima format 08 atau 628)
     */
    public static function formatPhone($number) {
        $cleaned = preg_replace('/[^0-9]/', '', (string)$number);
        if (empty($cleaned)) {
            return '';
        }

        // Jika diawali 62, ubah ke 08 jika perlu atau biarkan 62
        if (substr($cleaned, 0, 2) === '62') {
            return '0' . substr($cleaned, 2);
        }

        // Jika tidak diawali 0, tambahkan 0
        if (substr($cleaned, 0, 1) !== '0') {
            return '0' . $cleaned;
        }

        return $cleaned;
    }
}
