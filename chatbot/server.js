/**
 * FaceAbsen Chatbot AI Server
 * Powered by Node.js, MySQL & NVIDIA NIM API
 */

const http = require('http');
const https = require('https');
const mysql = require('mysql2/promise');

const PORT = process.env.PORT || 3001;
const NVIDIA_API_KEY = process.env.NVIDIA_API_KEY || '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';
const MODEL_NAME = process.env.MODEL_NAME || 'deepseek-ai/deepseek-v4.1-flash';
const FALLBACK_MODEL = process.env.FALLBACK_MODEL || 'meta/llama-3.2-11b-vision-instruct';

// Konfigurasi Database (Read-Only)
const DB_CONFIG = {
  host: process.env.DB_HOST || '127.0.0.1',
  port: parseInt(process.env.DB_PORT || '3306'),
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASS || '',
  database: process.env.DB_NAME || 'faceabsen_db'
};

const CODING_REFUSAL_MESSAGE = "Maaf, saya tidak diizinkan untuk membuat atau membahas kode program.";

// Deteksi apakah pesan pengguna meminta bantuan coding/skrip pemrograman
function isCodingRequest(text) {
  if (!text || typeof text !== 'string') return false;
  const lower = text.toLowerCase();
  
  const codingPatterns = [
    /\b(coding|koding|kodingkan|pemrograman|programmer)\b/i,
    /\b(bikin|buat|buatkan|tulis|tuliskan|contohkan|kasih|generate)\s+(kode|code|skrip|script|program|fungsi|function|algoritma|query)\b/i,
    /\b(source\s*code|sintaks|syntax|query\s+sql|buatkan\s+query)\b/i,
    /\b(javascript|python|php|html|css|java|c\+\+|c#|golang|bash|shell|typescript)\b/i,
    /\b(console\.log|echo\s+['"]|print\(|def\s+[a-z_]|class\s+[a-z_]|import\s+[a-z_]|<\?php)\b/i
  ];

  return codingPatterns.some(pattern => pattern.test(lower));
}

// Membaca seluruh data database FaceAbsen (HANYA SELECT / READ-ONLY)
async function getSchoolDatabaseData() {
  try {
    const conn = await mysql.createConnection(DB_CONFIG);

    // 1. Data Peserta Didik (Students)
    const [students] = await conn.query(`
      SELECT s.nis, s.phone, u.name, u.email, c.name as class_name, c.level,
             IF(s.face_model_identifier IS NOT NULL AND s.face_model_identifier != '', 'Terdaftar', 'Belum Terdaftar') as status_wajah
      FROM students s
      JOIN users u ON s.user_id = u.id
      JOIN classes c ON s.class_id = c.id
      WHERE u.status = 'active'
      ORDER BY c.level ASC, c.name ASC, u.name ASC
    `);

    // 2. Data Guru / Tenaga Pengajar (Teachers)
    const [teachers] = await conn.query(`
      SELECT t.nip, t.phone, u.name, u.email, u.status
      FROM teachers t
      JOIN users u ON t.user_id = u.id
      WHERE u.status = 'active'
      ORDER BY u.name ASC
    `);

    // 3. Data Rombongan Belajar / Kelas (Classes)
    const [classes] = await conn.query(`
      SELECT c.id, c.name as class_name, c.level, COUNT(s.id) as total_students
      FROM classes c
      LEFT JOIN students s ON s.class_id = c.id
      GROUP BY c.id, c.name, c.level
      ORDER BY c.level ASC, c.name ASC
    `);

    // 4. Data Pengaturan Sistem Presensi (Settings)
    const [settings] = await conn.query(`
      SELECT setting_key, setting_value FROM settings
    `);

    // 5. Data Pengguna / Akun (Users - Tanpa Password)
    const [users] = await conn.query(`
      SELECT id, username, name, email, role, status,
             DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') as created_at
      FROM users
      ORDER BY FIELD(role, 'super_user', 'guru', 'peserta_didik'), name ASC
    `);

    // 6. Rekapitulasi Presensi Hari Ini (Daily Summary)
    const [dailySummaryRows] = await conn.query(`
      SELECT
        COUNT(*) as total_records,
        COALESCE(SUM(CASE WHEN a.status = 'hadir' THEN 1 ELSE 0 END), 0) as hadir,
        COALESCE(SUM(CASE WHEN a.status = 'terlambat' THEN 1 ELSE 0 END), 0) as terlambat,
        COALESCE(SUM(CASE WHEN u.role = 'peserta_didik' AND a.status IN ('hadir', 'terlambat') THEN 1 ELSE 0 END), 0) as siswa_hadir,
        COALESCE(SUM(CASE WHEN u.role = 'guru' AND a.status IN ('hadir', 'terlambat') THEN 1 ELSE 0 END), 0) as guru_hadir
      FROM attendances a
      JOIN users u ON a.user_id = u.id
      WHERE a.attendance_date = CURRENT_DATE()
    `);
    const dailySummary = dailySummaryRows[0] || {
      total_records: 0,
      hadir: 0,
      terlambat: 0,
      siswa_hadir: 0,
      guru_hadir: 0
    };

    // 7. Analitik Kehadiran Per Rombel / Kelas Hari Ini
    const [classSummary] = await conn.query(`
      SELECT c.id, c.name as class_name, c.level,
             COUNT(s.id) as total_students,
             COALESCE(SUM(CASE WHEN a.status = 'hadir' THEN 1 ELSE 0 END), 0) as hadir,
             COALESCE(SUM(CASE WHEN a.status = 'terlambat' THEN 1 ELSE 0 END), 0) as terlambat
      FROM classes c
      LEFT JOIN students s ON s.class_id = c.id
      LEFT JOIN attendances a ON a.user_id = s.user_id AND a.attendance_date = CURRENT_DATE()
      GROUP BY c.id, c.name, c.level
      ORDER BY c.level ASC, c.name ASC
    `);

    // 8. Riwayat Presensi Terbaru (Recent Attendances)
    const [recentAttendances] = await conn.query(`
      SELECT a.id, u.name, u.role,
             DATE_FORMAT(a.attendance_date, '%Y-%m-%d') as attendance_date,
             a.attendance_time, a.status, a.method, a.confidence_score
      FROM attendances a
      JOIN users u ON a.user_id = u.id
      ORDER BY a.attendance_date DESC, a.attendance_time DESC
      LIMIT 15
    `);

    // 9. Daftar yang Hadir Hari Ini (Today Attendees)
    const [todayAttendees] = await conn.query(`
      SELECT a.id, u.name, u.role, a.status,
             TIME_FORMAT(a.attendance_time, '%H:%i') as attendance_time,
             a.method, a.confidence_score
      FROM attendances a
      JOIN users u ON a.user_id = u.id
      WHERE a.attendance_date = CURRENT_DATE()
      ORDER BY a.attendance_time ASC
    `);

    // 10. Riwayat Pesan WhatsApp Gateway (Broadcast Messages)
    let broadcastMessages = [];
    try {
      const [bm] = await conn.query(`
        SELECT id, recipient_type, target_names, total_recipients, message, status,
               DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') as created_at
        FROM broadcast_messages
        ORDER BY id DESC LIMIT 10
      `);
      broadcastMessages = bm;
    } catch (e) {}

    await conn.end();
    return { students, teachers, classes, settings, users, dailySummary, classSummary, recentAttendances, todayAttendees, broadcastMessages };
  } catch (err) {
    console.error('Database read error:', err.message);
    return null;
  }
}

// Helper: Kirim JSON Response dengan header CORS
function sendJson(res, statusCode, data) {
  const payload = JSON.stringify(data);
  res.writeHead(statusCode, {
    'Content-Type': 'application/json; charset=utf-8',
    'Access-Control-Allow-Origin': '*',
    'Access-Control-Allow-Methods': 'GET, POST, OPTIONS',
    'Access-Control-Allow-Headers': 'Content-Type, Authorization, X-Requested-With',
    'Content-Length': Buffer.byteLength(payload)
  });
  res.end(payload);
}

// Helper: Identitas pengguna dan bot
function getIdentityResponse(userMessage, userName, userRole) {
  const lower = (userMessage || '').toLowerCase().trim();
  const roleStr = (userRole || '').toLowerCase();
  const isSuperUser = roleStr.includes('super_user') || roleStr.includes('admin');
  const isGuru = roleStr.includes('guru') || roleStr === 'teacher';

  const isSiapaSaya = /\b(siapa\s+(saya|aku|diriku)|(saya|aku)\s+siapa|siapakah\s+(saya|aku|diriku)|identitas\s+saya|siapa\s+nama\s+saya|siapakah\s+nama\s+saya)\b/i.test(lower);
  const isSiapaAnda = /\b(siapa\s+(anda|kamu|kau|dirimu|bot|chatbot)|(anda|kamu|kau)\s+siapa|siapakah\s+(anda|kamu|kau|dirimu|bot|chatbot)|identitas\s+(anda|kamu)|siapa\s+nama\s+(anda|kamu)|nama\s+kamu\s+siapa)\b/i.test(lower);

  let jabatan = 'Peserta Didik (Siswa)';
  if (isSuperUser) {
    jabatan = 'Super User (Administrator)';
  } else if (isGuru) {
    jabatan = 'Guru / Tenaga Pendidik';
  }

  if (isSiapaSaya && isSiapaAnda) {
    return `Anda adalah ${userName}, dengan jabatan sebagai ${jabatan}. Saya adalah FaceAbsen AI, asisten virtual untuk sistem presensi sekolah FaceAbsen.`;
  }
  if (isSiapaSaya) {
    return `Anda adalah ${userName}, dengan jabatan sebagai ${jabatan}.`;
  }
  if (isSiapaAnda) {
    return 'Saya adalah FaceAbsen AI, asisten virtual untuk sistem presensi sekolah FaceAbsen.';
  }

  return null;
}

// Helper cerdas: Membedakan pertanyaan jumlah ("berapa banyak" -> hanya angka) dan daftar nama ("sebutkan namanya" / "siapa saja" -> daftar nama) untuk Super User
function handleSuperUserQuery(userMessage, dbData, clientHistory = []) {
  if (!dbData) return null;
  const rawLower = (userMessage || '').toLowerCase().trim();
  const lower = rawLower.replace(/[?!.]+$/, '').trim();

  // Deteksi intensi
  const isBerapaBanyak = /\b(berapa|jumlah|total|banyaknya)\b/i.test(lower) || lower.startsWith('ada berapa');
  const isSebutkanNama = /\b(sebutkan|daftar|siapa\s+saja|mana\s+saja|nama(-|\s*)namanya|namanya|tampilkan\s+nama|siapa\s+yang)\b/i.test(lower) ||
                         lower.startsWith('siapa ') || lower.startsWith('siapakah ');

  if (!isBerapaBanyak && !isSebutkanNama) {
    return null;
  }

  // Tentukan topik (siswa, guru, hadir, terlambat, belum hadir, kelas, user, setting, tabel)
  let topic = null;

  if (lower.includes('terlambat') || lower.includes('telat')) {
    topic = 'terlambat';
  } else if (lower.includes('belum') || lower.includes('tidak hadir')) {
    topic = 'belum_hadir';
  } else if (lower.includes('hadir') || lower.includes('masuk') || lower.includes('kehadiran') || (lower.includes('absen') && !lower.includes('belum'))) {
    topic = 'hadir';
  } else if (lower.includes('siswa') || lower.includes('peserta didik') || lower.includes('murid')) {
    topic = 'siswa';
  } else if (lower.includes('guru') || lower.includes('pengajar') || lower.includes('pendidik')) {
    topic = 'guru';
  } else if (lower.includes('kelas') || lower.includes('rombel')) {
    topic = 'kelas';
  } else if (lower.includes('user') || lower.includes('pengguna') || lower.includes('akun')) {
    topic = 'user';
  } else if (lower.includes('setting') || lower.includes('pengaturan')) {
    topic = 'setting';
  } else if (lower.includes('tabel') || lower.includes('table')) {
    topic = 'tabel';
  }

  // Jika topik belum jelas (contoh: user hanya ketik "sebutkan namanya", "siapa saja", atau "berapa banyak"), cek riwayat percakapan sebelumnya
  if (!topic && Array.isArray(clientHistory) && clientHistory.length > 0) {
    const historyText = clientHistory.slice(-3).map(h => (h.content || '').toLowerCase()).join(' ');
    if (historyText.includes('terlambat') || historyText.includes('telat')) {
      topic = 'terlambat';
    } else if (historyText.includes('belum')) {
      topic = 'belum_hadir';
    } else if (historyText.includes('hadir') || historyText.includes('absen')) {
      topic = 'hadir';
    } else if (historyText.includes('siswa') || historyText.includes('murid')) {
      topic = 'siswa';
    } else if (historyText.includes('guru') || historyText.includes('pengajar')) {
      topic = 'guru';
    } else if (historyText.includes('kelas') || historyText.includes('rombel')) {
      topic = 'kelas';
    } else if (historyText.includes('user') || historyText.includes('akun')) {
      topic = 'user';
    }
  }

  if (!topic) return null;

  const daily = dbData.dailySummary || {};
  const hadir = Number(daily.hadir || 0);
  const telat = Number(daily.terlambat || 0);
  const totalHadir = hadir + telat;
  const siswaHadir = Number(daily.siswa_hadir || 0);
  const guruHadir = Number(daily.guru_hadir || 0);
  const totStudents = dbData.students ? dbData.students.length : 0;
  const totTeachers = dbData.teachers ? dbData.teachers.length : 0;
  const siswaBelum = Math.max(0, totStudents - siswaHadir);
  const guruBelum = Math.max(0, totTeachers - guruHadir);
  const totalBelum = siswaBelum + guruBelum;

  // KASUS 1: USER TANYA "BERAPA BANYAK" / JUMLAH (HANYA ANGKA TOTALNYA SAJA)
  if (isBerapaBanyak && !isSebutkanNama) {
    if (topic === 'siswa') {
      return `Total siswa: ${totStudents} orang.`;
    }
    if (topic === 'guru') {
      return `Total guru: ${totTeachers} orang.`;
    }
    if (topic === 'hadir') {
      return `Total hadir hari ini: ${totalHadir} orang (Siswa: ${siswaHadir}, Guru: ${guruHadir}).`;
    }
    if (topic === 'terlambat') {
      return `Total terlambat hari ini: ${telat} orang.`;
    }
    if (topic === 'belum_hadir') {
      return `Total belum hadir: ${totalBelum} orang (Siswa: ${siswaBelum}, Guru: ${guruBelum}).`;
    }
    if (topic === 'kelas') {
      const totClasses = dbData.classes ? dbData.classes.length : 0;
      return `Total kelas: ${totClasses} rombel.`;
    }
    if (topic === 'user') {
      const totUsers = dbData.users ? dbData.users.length : 0;
      return `Total akun pengguna: ${totUsers} akun.`;
    }
    if (topic === 'setting') {
      const totSettings = dbData.settings ? dbData.settings.length : 0;
      return `Total pengaturan: ${totSettings} konfigurasi.`;
    }
    if (topic === 'tabel') {
      return `Total tabel: 7 tabel (attendances, broadcast_messages, classes, settings, students, teachers, users).`;
    }
  }

  // KASUS 2: USER TANYA "SEBUTKAN NAMANYA" / "SIAPA SAJA" (DAFTAR NAMA)
  if (isSebutkanNama && !isBerapaBanyak) {
    if (topic === 'hadir') {
      const attendees = dbData.todayAttendees || [];
      if (attendees.length === 0) {
        return 'Belum ada yang hadir hari ini.';
      }
      return attendees.map(a => `- ${a.name} (${a.role === 'guru' ? 'Guru' : 'Siswa'}) | Status: ${a.status.toUpperCase()} | Pukul: ${a.attendance_time}`).join('\n');
    }

    if (topic === 'terlambat') {
      const attendees = (dbData.todayAttendees || []).filter(a => a.status === 'terlambat');
      if (attendees.length === 0) {
        return 'Tidak ada yang terlambat hari ini.';
      }
      return attendees.map(a => `- ${a.name} (${a.role === 'guru' ? 'Guru' : 'Siswa'}) | Status: TERLAMBAT | Pukul: ${a.attendance_time}`).join('\n');
    }

    if (topic === 'belum_hadir') {
      const attendedUserNames = new Set((dbData.todayAttendees || []).map(a => a.name));
      const unStudents = (dbData.students || []).filter(s => !attendedUserNames.has(s.name));
      const unTeachers = (dbData.teachers || []).filter(t => !attendedUserNames.has(t.name));
      const lines = [];
      unStudents.forEach(s => lines.push(`- [Siswa] ${s.name} (Kelas: ${s.class_name})`));
      unTeachers.forEach(t => lines.push(`- [Guru] ${t.name}`));
      if (lines.length === 0) {
        return 'Semua siswa dan guru sudah hadir hari ini.';
      }
      return lines.join('\n');
    }

    if (topic === 'siswa') {
      if (!dbData.students || dbData.students.length === 0) {
        return 'Belum ada data siswa terdaftar.';
      }
      return dbData.students.map(s => `- ${s.name} (NIS: ${s.nis}, Kelas: ${s.class_name}${s.phone ? ', Telp: ' + s.phone : ''})`).join('\n');
    }

    if (topic === 'guru') {
      if (!dbData.teachers || dbData.teachers.length === 0) {
        return 'Belum ada data guru terdaftar.';
      }
      return dbData.teachers.map(t => `- ${t.name} (NIP: ${t.nip}${t.phone ? ', Telp: ' + t.phone : ''})`).join('\n');
    }

    if (topic === 'kelas') {
      if (!dbData.classes || dbData.classes.length === 0) {
        return 'Belum ada data kelas terdaftar.';
      }
      return dbData.classes.map(c => `- Kelas ${c.class_name} (Tingkat ${c.level})`).join('\n');
    }

    if (topic === 'user') {
      if (!dbData.users || dbData.users.length === 0) {
        return 'Belum ada data pengguna terdaftar.';
      }
      return dbData.users.map(u => `- [${u.role.toUpperCase()}] ${u.name} (username: ${u.username})`).join('\n');
    }
  }

  // KASUS 3: JIKA PENGGUNA BERTANYA JUMLAH SEKALIGUS NAMA ("berapa banyak dan sebutkan namanya")
  if (isBerapaBanyak && isSebutkanNama) {
    if (topic === 'siswa') {
      const list = dbData.students.map(s => `- ${s.name} (NIS: ${s.nis}, Kelas: ${s.class_name}${s.phone ? ', Telp: ' + s.phone : ''})`).join('\n');
      return `Total siswa: ${totStudents} orang.\nDaftar nama siswa:\n${list}`;
    }
    if (topic === 'guru') {
      const list = dbData.teachers.map(t => `- ${t.name} (NIP: ${t.nip}${t.phone ? ', Telp: ' + t.phone : ''})`).join('\n');
      return `Total guru: ${totTeachers} orang.\nDaftar nama guru:\n${list}`;
    }
    if (topic === 'hadir') {
      const attendees = dbData.todayAttendees || [];
      const list = attendees.length > 0 
        ? attendees.map(a => `- ${a.name} (${a.role === 'guru' ? 'Guru' : 'Siswa'}) | Status: ${a.status.toUpperCase()} | Pukul: ${a.attendance_time}`).join('\n')
        : 'Belum ada yang hadir hari ini.';
      return `Total hadir hari ini: ${totalHadir} orang (Siswa: ${siswaHadir}, Guru: ${guruHadir}).\n${list}`;
    }
  }

  return null;
}

// Fallback cerdas: Menjawab langsung dari database lokal jika layanan AI eksternal sedang mengalami gangguan/timeout
function getDirectDatabaseFallbackResponse(userMessage, dbData, isSuperUser, userName = 'Pengguna', userRole = 'peserta_didik', clientHistory = []) {
  const identityReply = getIdentityResponse(userMessage, userName, userRole);
  if (identityReply) {
    return identityReply;
  }

  if (!dbData) {
    return 'Data database belum siap.';
  }

  // Cek apakah pertanyaan adalah jenis jumlah / sebutkan nama untuk Super User
  if (isSuperUser) {
    const suReply = handleSuperUserQuery(userMessage, dbData, clientHistory);
    if (suReply) {
      return suReply;
    }
  }

  const lower = (userMessage || '').toLowerCase();

  // 1. Seluruh Tabel yang Ada di Database
  if (lower.includes('seluruh tabel') || lower.includes('semua tabel') || lower.includes('daftar tabel') || (lower.includes('tabel') && (lower.includes('apa') || lower.includes('database')))) {
    const bmCount = dbData.broadcastMessages ? dbData.broadcastMessages.length : 0;
    return `Tabel di database faceabsen_db (7 tabel):\n` +
      `1. attendances (${dbData.recentAttendances ? dbData.recentAttendances.length : 0} riwayat, ${dbData.dailySummary.total_records} presensi hari ini)\n` +
      `2. broadcast_messages (${bmCount} log pesan WhatsApp terkirim via Fonnte)\n` +
      `3. classes (${dbData.classes.length} baris/kelas)\n` +
      `4. settings (${dbData.settings.length} baris konfigurasi)\n` +
      `5. students (${dbData.students.length} baris/siswa)\n` +
      `6. teachers (${dbData.teachers.length} baris/guru)\n` +
      `7. users (${dbData.users.length} baris/akun)`;
  }

  // 2. Tabel Settings
  if (lower.includes('setting') || lower.includes('pengaturan') || lower.includes('jadwal') || lower.includes('jam') || lower.includes('waktu') || lower.includes('teachable') || lower.includes('model') || lower.includes('akurasi') || lower.includes('threshold')) {
    return dbData.settings.map(s => `- ${s.setting_key}: ${s.setting_value}`).join('\n');
  }

  // 3. Tabel Students / Siswa
  if (lower.includes('student') || lower.includes('siswa') || lower.includes('peserta didik') || lower.includes('murid') || lower.includes('nis')) {
    return dbData.students.map(s => `- ${s.name} (NIS: ${s.nis}) | Kelas: ${s.class_name} (Tingkat ${s.level})${s.phone ? ' | Telp: ' + s.phone : ''} | Status Wajah AI: ${s.status_wajah}`).join('\n');
  }

  // 4. Tabel Teachers / Guru
  if (lower.includes('teacher') || lower.includes('guru') || lower.includes('pengajar') || lower.includes('pendidik') || lower.includes('nip')) {
    return dbData.teachers.map(t => `- ${t.name} (NIP: ${t.nip})${t.phone ? ' | Telp: ' + t.phone : ''} | Status: ${t.status}`).join('\n');
  }

  // 5. Tabel Classes / Rombel
  if (lower.includes('class') || lower.includes('rombel') || lower.includes('kelas') || lower.includes('tingkat')) {
    if (lower.includes('analitik')) {
      return dbData.classSummary.map(c => {
        const tot = Number(c.total_students || 0);
        const h = Number(c.hadir || 0);
        const t = Number(c.terlambat || 0);
        const masuk = h + t;
        const blm = Math.max(0, tot - masuk);
        const pct = tot > 0 ? Math.round((masuk / tot) * 100) : 0;
        return `- Kelas ${c.class_name} (Tingkat ${c.level}): Total ${tot} siswa | Hadir: ${h} | Terlambat: ${t} | Belum: ${blm} | Kehadiran: ${pct}%`;
      }).join('\n');
    }
    return dbData.classes.map(c => `- ID: ${c.id} | Kelas: ${c.class_name} | Tingkat: ${c.level} | Total Siswa: ${c.total_students}`).join('\n');
  }

  // 6. Tabel Users / Akun
  if (lower.includes('user') || lower.includes('pengguna') || lower.includes('akun') || lower.includes('role') || lower.includes('admin')) {
    return dbData.users.map(u => `- [${u.role.toUpperCase()}] ${u.name} (username: ${u.username}) | Status: ${u.status}`).join('\n');
  }

  // 7. Tabel Attendances / Log Presensi
  if (lower.includes('attendance') || lower.includes('log') || lower.includes('riwayat') || lower.includes('history') || lower.includes('terbaru') || lower.includes('aktivitas')) {
    if (!dbData.recentAttendances || dbData.recentAttendances.length === 0) {
      return 'Belum ada catatan di tabel attendances.';
    }
    return dbData.recentAttendances.map(a => 
      `- ${a.attendance_date} ${a.attendance_time} | ${a.name} (${a.role}) | Status: ${a.status.toUpperCase()} | Metode: ${a.method} (${a.confidence_score || 0}%)`
    ).join('\n');
  }

  // 8. Rekapitulasi Presensi
  if (lower.includes('rekap') || lower.includes('kehadiran') || lower.includes('absen') || lower.includes('presensi') || lower.includes('hadir') || lower.includes('terlambat')) {
    const totStudents = dbData.students.length;
    const totTeachers = dbData.teachers.length;
    const daily = dbData.dailySummary;
    const hadir = Number(daily.hadir || 0);
    const telat = Number(daily.terlambat || 0);
    const totalMasuk = hadir + telat;
    const siswaHadir = Number(daily.siswa_hadir || 0);
    const guruHadir = Number(daily.guru_hadir || 0);
    const siswaBelum = Math.max(0, totStudents - siswaHadir);
    const guruBelum = Math.max(0, totTeachers - guruHadir);
    const pctSiswa = totStudents > 0 ? Math.round((siswaHadir / totStudents) * 100) : 0;
    const pctGuru = totTeachers > 0 ? Math.round((guruHadir / totTeachers) * 100) : 0;

    return `- Total Terdaftar: ${totStudents} Siswa, ${totTeachers} Guru\n` +
      `- Presensi Tercatat: ${totalMasuk} orang (${hadir} Tepat Waktu, ${telat} Terlambat)\n` +
      `- Kehadiran Siswa: ${siswaHadir}/${totStudents} (${pctSiswa}%, ${siswaBelum} belum absen)\n` +
      `- Kehadiran Guru: ${guruHadir}/${totTeachers} (${pctGuru}%, ${guruBelum} belum absen)`;
  }

  // 9. Database / Semua Data
  if (lower.includes('database') || lower.includes('semua data') || lower.includes('master')) {
    return `Tabel di database faceabsen_db (6 tabel):\n` +
      `1. attendances: ${dbData.dailySummary.total_records} presensi tercatat hari ini\n` +
      `2. classes: ${dbData.classes.length} kelas terdaftar\n` +
      `3. settings: ${dbData.settings.length} konfigurasi sistem\n` +
      `4. students: ${dbData.students.length} peserta didik\n` +
      `5. teachers: ${dbData.teachers.length} guru\n` +
      `6. users: ${dbData.users.length} akun pengguna`;
  }

  // 10. Default
  if (isSuperUser) {
    return `- Tabel attendances: ${dbData.dailySummary.total_records} presensi hari ini\n` +
      `- Tabel classes: ${dbData.classes.length} kelas\n` +
      `- Tabel settings: ${dbData.settings.length} pengaturan\n` +
      `- Tabel students: ${dbData.students.length} siswa\n` +
      `- Tabel teachers: ${dbData.teachers.length} guru\n` +
      `- Tabel users: ${dbData.users.length} akun`;
  }

  return `Sistem FaceAbsen: ${dbData.students.length} siswa, ${dbData.teachers.length} guru terdaftar.`;
}

let cachedKeyInfo = null;
let lastKeyFetch = 0;

async function getActiveApiKeyInfo() {
  const now = Date.now();
  if (cachedKeyInfo && (now - lastKeyFetch < 10000)) {
    return cachedKeyInfo;
  }
  try {
    const conn = await mysql.createConnection(DB_CONFIG);
    const [rows] = await conn.query("SELECT id, name, provider, api_key FROM api_keys WHERE is_active = 1 LIMIT 1");
    await conn.end();
    if (rows && rows.length > 0 && rows[0].api_key) {
      const row = rows[0];
      const isGemini = (row.provider && (row.provider.toLowerCase().includes('gemini') || row.provider.toLowerCase().includes('google'))) ||
                       row.api_key.startsWith('AQ.') || row.api_key.startsWith('AIza');
      cachedKeyInfo = {
        apiKey: row.api_key,
        provider: row.provider || (isGemini ? 'Google Gemini' : 'NVIDIA NIM'),
        isGemini: isGemini,
        name: row.name
      };
      lastKeyFetch = now;
      return cachedKeyInfo;
    }
  } catch (e) {
    // fallback
  }
  const isGeminiDefault = NVIDIA_API_KEY.startsWith('AQ.') || NVIDIA_API_KEY.startsWith('AIza');
  return {
    apiKey: NVIDIA_API_KEY,
    provider: isGeminiDefault ? 'Google Gemini' : 'NVIDIA NIM',
    isGemini: isGeminiDefault,
    name: 'Default Key'
  };
}

async function getActiveApiKey() {
  const info = await getActiveApiKeyInfo();
  return info.apiKey;
}

// Helper: Panggil Google Gemini generateContent API
async function queryGeminiAI(clientHistory, userMessage, systemPrompt, apiKey) {
  return new Promise((resolve, reject) => {
    const contents = [];
    if (Array.isArray(clientHistory)) {
      clientHistory.slice(-4).forEach(item => {
        if (item && item.content && typeof item.content === 'string') {
          contents.push({
            role: item.role === 'assistant' || item.role === 'model' ? 'model' : 'user',
            parts: [{ text: item.content }]
          });
        }
      });
    }
    contents.push({
      role: 'user',
      parts: [{ text: userMessage }]
    });

    const postData = JSON.stringify({
      system_instruction: {
        parts: [{ text: systemPrompt }]
      },
      contents: contents,
      generationConfig: {
        temperature: 0.3,
        maxOutputTokens: 1200
      }
    });

    const modelName = 'gemini-3.5-flash';
    const options = {
      hostname: 'generativelanguage.googleapis.com',
      port: 443,
      path: `/v1beta/models/${modelName}:generateContent?key=${encodeURIComponent(apiKey)}`,
      method: 'POST',
      family: 4,
      headers: {
        'Content-Type': 'application/json',
        'Content-Length': Buffer.byteLength(postData)
      },
      timeout: 60000
    };

    let isHandled = false;
    const req = https.request(options, (res) => {
      let rawData = '';
      res.on('data', chunk => { rawData += chunk; });
      res.on('end', () => {
        if (isHandled) return;
        isHandled = true;
        try {
          const parsed = JSON.parse(rawData);
          if (res.statusCode >= 200 && res.statusCode < 300) {
            let reply = parsed.candidates?.[0]?.content?.parts?.[0]?.text || 'Maaf, tidak ada balasan.';
            if (reply.includes('```') || /<\?php|<script|def\s+|function\s*\(/.test(reply)) {
              reply = CODING_REFUSAL_MESSAGE;
            }
            resolve({ success: true, reply, modelUsed: `Google Gemini (${modelName})` });
          } else {
            const errMsg = parsed.error?.message || `Gemini API error: HTTP ${res.statusCode}`;
            reject(new Error(errMsg));
          }
        } catch (e) {
          reject(new Error(`Gagal memparsing respons Gemini: ${e.message}`));
        }
      });
    });

    req.on('timeout', () => {
      req.destroy();
      if (!isHandled) {
        isHandled = true;
        reject(new Error('Koneksi ke Google Gemini API timeout.'));
      }
    });

    req.on('error', err => {
      if (!isHandled) {
        isHandled = true;
        reject(new Error(`Gagal menghubungi Google Gemini API: ${err.message}`));
      }
    });

    req.write(postData);
    req.end();
  });
}

// Helper: Panggil NVIDIA NIM Chat Completions API
async function queryNvidiaAI(messages, modelToUse = MODEL_NAME, keyToUse = null) {
  const currentKey = keyToUse || await getActiveApiKey();
  return new Promise((resolve, reject) => {
    const postData = JSON.stringify({
      model: modelToUse,
      messages: messages,
      temperature: 0.3,
      top_p: 0.8,
      max_tokens: 500
    });

    const isPrimary = (modelToUse === MODEL_NAME && MODEL_NAME !== FALLBACK_MODEL);
    let isHandled = false;

    const options = {
      hostname: 'integrate.api.nvidia.com',
      port: 443,
      path: '/v1/chat/completions',
      method: 'POST',
      family: 4, // Paksa IPv4 untuk menghindari delay/timeout IPv6
      headers: {
        'Authorization': `Bearer ${currentKey}`,
        'Content-Type': 'application/json',
        'Content-Length': Buffer.byteLength(postData)
      },
      timeout: isPrimary ? 7000 : 8000
    };

    const tryFallbackOrReject = (err) => {
      if (isHandled) return;
      isHandled = true;
      if (isPrimary) {
        console.warn(`[WARN] Model ${modelToUse} gagal/timeout (${err.message}), beralih ke fallback ${FALLBACK_MODEL}...`);
        queryNvidiaAI(messages, FALLBACK_MODEL, currentKey).then(resolve).catch(reject);
      } else {
        reject(err);
      }
    };

    const req = https.request(options, (res) => {
      let rawData = '';
      res.on('data', (chunk) => { rawData += chunk; });
      res.on('end', () => {
        if (isHandled) return;
        try {
          const parsed = JSON.parse(rawData);
          if (res.statusCode >= 200 && res.statusCode < 300) {
            isHandled = true;
            let reply = parsed.choices?.[0]?.message?.content || 'Maaf, saya tidak dapat memproses jawaban saat ini.';
            // Pengaman tambahan: Jika AI secara tidak sengaja menghasilkan blok kode, tangkal
            if (reply.includes('```') || /<\?php|<script|def\s+|function\s*\(/.test(reply)) {
              reply = CODING_REFUSAL_MESSAGE;
            }
            resolve({ success: true, reply, raw: parsed, modelUsed: modelToUse });
          } else {
            const errMsg = parsed.detail || parsed.message || `NVIDIA API error: HTTP ${res.statusCode}`;
            tryFallbackOrReject(new Error(errMsg));
          }
        } catch (e) {
          tryFallbackOrReject(new Error(`Gagal memparsing respons AI: ${e.message}`));
        }
      });
    });

    req.on('timeout', () => {
      req.destroy();
      tryFallbackOrReject(new Error('Koneksi ke NVIDIA API melebihi batas waktu (timeout).'));
    });

    req.on('error', (err) => {
      tryFallbackOrReject(new Error(`Gagal menghubungi NVIDIA API: ${err.message}`));
    });

    req.write(postData);
    req.end();
  });
}

// Buat HTTP Server
const server = http.createServer((req, res) => {
  const url = new URL(req.url, `http://${req.headers.host || 'localhost'}`);
  const pathname = url.pathname;

  // Handle CORS Preflight
  if (req.method === 'OPTIONS') {
    res.writeHead(204, {
      'Access-Control-Allow-Origin': '*',
      'Access-Control-Allow-Methods': 'GET, POST, OPTIONS',
      'Access-Control-Allow-Headers': 'Content-Type, Authorization, X-Requested-With'
    });
    return res.end();
  }

  // Healthcheck Endpoint
  if (req.method === 'GET' && (pathname === '/api/health' || pathname === '/health' || pathname === '/')) {
    return sendJson(res, 200, {
      status: 'ok',
      service: 'FaceAbsen Chatbot AI Service',
      engine: 'Node.js',
      provider: 'NVIDIA NIM API',
      model: MODEL_NAME,
      database_access: 'enabled (read-only)',
      coding_allowed: false,
      timestamp: new Date().toISOString()
    });
  }

  // Chat Endpoint
  if (req.method === 'POST' && pathname === '/api/chat') {
    let body = '';
    req.on('data', chunk => {
      body += chunk;
      if (body.length > 512 * 1024) {
        req.destroy();
      }
    });

    req.on('end', async () => {
      try {
        const payload = JSON.parse(body || '{}');
        const userMessage = (payload.message || '').trim();
        const userName = payload.userName || 'Pengguna';
        const userRole = (payload.userRole || 'peserta_didik').toLowerCase();
        const clientHistory = Array.isArray(payload.history) ? payload.history : [];

        if (!userMessage) {
          return sendJson(res, 400, {
            success: false,
            error: 'Pesan tidak boleh kosong.'
          });
        }

        // 1. CEK ATURAN: JANGAN IZINKAN CODING APAPUN
        if (isCodingRequest(userMessage)) {
          return sendJson(res, 200, {
            success: true,
            reply: CODING_REFUSAL_MESSAGE,
            model: MODEL_NAME
          });
        }

        // 2. CEK PERTANYAAN IDENTITAS ("siapa saya" / "siapa anda")
        const identityReply = getIdentityResponse(userMessage, userName, userRole);
        if (identityReply) {
          return sendJson(res, 200, {
            success: true,
            reply: identityReply,
            model: 'FaceAbsen AI'
          });
        }

        // 3. AKSES DATABASE UNTUK MEMBACA DATA
        const dbData = await getSchoolDatabaseData();
        const isSuperUser = userRole.includes('super_user') || userRole.includes('admin');
        const isGuru = userRole.includes('guru') || userRole === 'teacher';
        const roleLabel = isSuperUser ? 'Super User / Administrator' : (isGuru ? 'Guru / Tenaga Pendidik' : 'Peserta Didik (Siswa)');

        // 4. CEK PERTANYAAN KHUSUS SUPER USER ("berapa banyak" -> angka, "sebutkan namanya" -> daftar nama)
        if (isSuperUser) {
          const suReply = handleSuperUserQuery(userMessage, dbData, clientHistory);
          if (suReply) {
            return sendJson(res, 200, {
              success: true,
              reply: suReply,
              model: 'FaceAbsen AI'
            });
          }
        }

        let systemPrompt = '';

        if (isSuperUser) {
          // =========================================================================
          // ROLE: SUPER USER (ADMINISTRATOR) - AKSES LENGKAP SEMUA DATABASE
          // =========================================================================
          let fullDbContext = 'DATA DATABASE TIDAK TERSEDIA.';
          if (dbData) {
            const totStudents = dbData.students.length;
            const totTeachers = dbData.teachers.length;
            const daily = dbData.dailySummary;
            const totalHadirSekolah = Number(daily.hadir || 0);
            const totalTerlambatSekolah = Number(daily.terlambat || 0);
            const totalSudahAbsen = totalHadirSekolah + totalTerlambatSekolah;
            const siswaHadir = Number(daily.siswa_hadir || 0);
            const guruHadir = Number(daily.guru_hadir || 0);
            const siswaBelumAbsen = Math.max(0, totStudents - siswaHadir);
            const guruBelumAbsen = Math.max(0, totTeachers - guruHadir);

            const persentaseSiswa = totStudents > 0 ? Math.round((siswaHadir / totStudents) * 100) : 0;
            const persentaseGuru = totTeachers > 0 ? Math.round((guruHadir / totTeachers) * 100) : 0;

            const classAnalyticsRows = dbData.classSummary.map(c => {
              const cTot = Number(c.total_students || 0);
              const cHadir = Number(c.hadir || 0);
              const cTelat = Number(c.terlambat || 0);
              const cMasuk = cHadir + cTelat;
              const cBelum = Math.max(0, cTot - cMasuk);
              const cPct = cTot > 0 ? Math.round((cMasuk / cTot) * 100) : 0;
              return `- Kelas ${c.class_name} (Tingkat ${c.level}): Total ${cTot} siswa | Hadir: ${cHadir} | Terlambat: ${cTelat} | Belum Absen: ${cBelum} | Persentase Kehadiran: ${cPct}%`;
            }).join('\n');

            const studentRows = dbData.students.map(s => 
              `- ${s.name} | NIS: ${s.nis} | Kelas: ${s.class_name} (Tingkat ${s.level}) | Telp: ${s.phone || '-'} | Status Wajah AI: ${s.status_wajah}`
            ).join('\n');

            const teacherRows = dbData.teachers.map(t => 
              `- ${t.name} | NIP: ${t.nip} | Telp: ${t.phone || '-'} | Status: ${t.status}`
            ).join('\n');

            const classMasterRows = dbData.classes.map(c => 
              `- ${c.class_name} (Tingkat ${c.level}) | ID: ${c.id} | Jumlah Siswa: ${c.total_students}`
            ).join('\n');

            const settingRows = dbData.settings.map(s => 
              `- ${s.setting_key}: ${s.setting_value}`
            ).join('\n');

            const userRows = dbData.users.map(u => 
              `- [${u.role.toUpperCase()}] ${u.name} (username: ${u.username}) | Status: ${u.status} | Terdaftar: ${u.created_at}`
            ).join('\n');

            const recentAttendanceRows = (dbData.recentAttendances && dbData.recentAttendances.length > 0)
              ? dbData.recentAttendances.map(a => 
                  `- [${a.attendance_date} ${a.attendance_time}] ${a.name} (${a.role}) | Status: ${a.status} | Metode: ${a.method} | Akurasi: ${a.confidence_score ?? '-'}%`
                ).join('\n')
              : '- Belum ada data presensi yang tercatat.';

            const broadcastRows = (dbData.broadcastMessages && dbData.broadcastMessages.length > 0)
              ? dbData.broadcastMessages.map(b =>
                  `- [ID: ${b.id} | ${b.created_at}] Ke: ${b.target_names} (${b.recipient_type}) | Penerima: ${b.total_recipients} orang | Status: ${b.status} | Pesan: "${b.message}"`
                ).join('\n')
              : '- Belum ada log pesan WhatsApp.';

            fullDbContext = `
SELURUH DATA 7 TABEL DATABASE FACEABSEN (HAK AKSES RESMI SUPER USER):

1. TABEL 'attendances' (Presensi, Analitik Kehadiran & Riwayat Log):
   - Total Terdaftar: ${totStudents} Siswa, ${totTeachers} Guru
   - Presensi Tercatat Hari Ini: ${totalSudahAbsen} orang (Tepat Waktu: ${totalHadirSekolah}, Terlambat: ${totalTerlambatSekolah})
   - Kehadiran Siswa: ${siswaHadir} dari ${totStudents} siswa (${persentaseSiswa}% hadir, ${siswaBelumAbsen} belum absen)
   - Kehadiran Guru: ${guruHadir} dari ${totTeachers} guru (${persentaseGuru}% hadir, ${guruBelumAbsen} belum absen)
   - Log Presensi Terbaru:
${recentAttendanceRows}

2. TABEL 'broadcast_messages' (Riwayat Pesan WhatsApp Gateway Fonnte):
${broadcastRows}

3. TABEL 'classes' (Master Rombel / Kelas - Total: ${dbData.classes.length} Kelas):
${classMasterRows}
   - Rincian Kehadiran Per Rombel:
${classAnalyticsRows}

4. TABEL 'settings' (Pengaturan Sistem Presensi - Total: ${dbData.settings.length} Konfigurasi):
${settingRows}

5. TABEL 'students' (Master Peserta Didik - Total: ${totStudents} Siswa):
${studentRows}

6. TABEL 'teachers' (Master Tenaga Pengajar / Guru - Total: ${totTeachers} Guru):
${teacherRows}

7. TABEL 'users' (Master Akun Pengguna - Total: ${dbData.users.length} Akun):
${userRows}
`;
          }

          systemPrompt = `Kamu adalah "FaceAbsen AI", asisten analitik, manajemen, dan informasi seluruh database sistem presensi sekolah untuk Super User / Administrator.

Pengguna saat ini: ${userName} (Super User / Administrator)

HAK AKSES DATA DATABASE SUPER USER:
Super User / Administrator memiliki izin membaca SELURUH 7 TABEL DATABASE FaceAbsen yang tersedia:
1. Tabel 'attendances': Rekapitulasi presensi, analitik per rombel, dan riwayat log kehadiran real-time.
2. Tabel 'broadcast_messages': Riwayat pengiriman pesan WhatsApp ke guru dan siswa melalui gateway Fonnte.
3. Tabel 'classes': Master data rombongan belajar/kelas, tingkat rombel, dan jumlah siswa.
4. Tabel 'settings': Konfigurasi pengaturan sistem presensi (jam masuk, batas terlambat, jam pulang, toleransi akurasi wajah AI, URL model Teachable Machine, nama sekolah, fonnte_api_key).
5. Tabel 'students': Data peserta didik (nama, NIS, nomor telepon, kelas, tingkat, status pendaftaran wajah AI).
6. Tabel 'teachers': Data tenaga pengajar / guru (nama, NIP, nomor telepon, status keaktifan).
7. Tabel 'users': Data akun pengguna sistem (username, nama lengkap, role/peran, status akun).

ATURAN PALING UTAMA & KETAT (WAJIB DIPATUHI):
1. DILARANG KERAS MELAKUKAN CODING DALAM BENTUK APAPUN!
   - Jangan pernah menulis, membuat, memberikan, menjelaskan, atau membahas kode program dalam bahasa apapun (PHP, JS, Python, HTML, SQL, dsb).
   - Jika pengguna meminta coding, tolak langsung: "${CODING_REFUSAL_MESSAGE}"
2. JAWAB HANYA SESUAI PERTANYAAN DAN JANGAN MEMBUAT KOMENTAR APAPUN:
   - Jawab langsung ke inti apa yang ditanyakan pengguna secara to-the-point dan faktual dari data database.
   - Jangan menampilkan data atau topik lain yang tidak ditanyakan.
   - DILARANG MEMBUAT KOMENTAR, OPINI, EVALUASI, ATAU SARAN APAPUN (jangan mengomentari seperti "kehadiran baik", "perlu ditingkatkan", atau saran yang tidak diminta).
   - DILARANG MEMBUAT BASA-BASI, SALAM, MAUPUN KALIMAT PEMBUKA/PENUTUP (jangan gunakan "Halo", "Baik", "Tentu", "Berikut data...", "Semoga membantu", "Silakan tanyakan lagi", dll). Langsung sajikan data/jawaban.
3. BATASAN TOPIK: HANYA SEPUTAR SISTEM FACEABSEN DAN SELURUH DATA DATABASE-NYA.
   - Selain sistem FaceAbsen dan data di dalamnya, tolak langsung: "Maaf, saya hanya dapat membantu seputar sistem dan seluruh data FaceAbsen."
4. ATURAN IDENTITAS:
   - Jika pengguna menanyakan "siapa saya" atau variasinya: Jawab dengan menyebut nama pengguna dan jabatannya: "Anda adalah ${userName}, dengan jabatan sebagai Super User (Administrator)."
   - Jika pengguna menanyakan "siapa anda" atau variasinya: Jawab dengan menyebut diri chatbot: "Saya adalah FaceAbsen AI, asisten virtual untuk sistem presensi sekolah FaceAbsen."
5. ATURAN JAWABAN ANGKA vs DAFTAR NAMA (SUPER USER):
   - Jika pengguna menanyakan JUMLAH / "BERAPA BANYAK" / "ADA BERAPA":
     Jawab HANYA angka totalnya saja secara ringkas tanpa menyebutkan daftar nama!
     * Contoh: "ada berapa banyak siswa yang ada di sekolah ini" → Jawab angka jumlah siswa: "Total siswa: X orang."
     * Contoh: "ada berapa banyak yang hadir hari ini" → Jawab angka yang hadir (guru & siswa): "Total hadir hari ini: Z orang (Siswa: X, Guru: Y)."
     * Contoh: "ada berapa banyak guru" → Jawab angka jumlah guru: "Total guru: X orang."
     * Contoh: "ada berapa banyak kelas" → Jawab angka jumlah kelas: "Total kelas: X rombel."
   - Jika pengguna menanyakan DAFTAR / SEBUTKAN "NAMA NYA" / "SIAPA SAJA":
     Sajikan daftar nama-namanya sesuai data database!
     * Contoh: "siapa saja yang hadir nya" / "sebutkan nama yang hadir" → Sebutkan nama-nama yang sudah hadir hari ini (jika belum ada, jawab: "Belum ada yang hadir hari ini.").
     * Contoh: "sebutkan nama siswanya" / "siapa saja siswanya" → Sebutkan daftar nama siswa.
     * Contoh: "sebutkan nama gurunya" / "siapa saja gurunya" → Sebutkan daftar nama guru.

${fullDbContext}`;

        } else {
          // =========================================================================
          // ROLE: PESERTA DIDIK & GURU
          // =========================================================================
          let dbContext = '';
          if (dbData) {
            const studentLines = dbData.students.map(s => 
              `- ${s.name} | NIS: ${s.nis} | Kelas: ${s.class_name} | Telp: ${s.phone || '-'} | Wajah AI: ${s.status_wajah}`
            ).join('\n');

            const teacherLines = dbData.teachers.map(t => 
              `- ${t.name} | NIP: ${t.nip} | Telp: ${t.phone || '-'}`
            ).join('\n');

            dbContext = `
DATA RESMI DATABASE FACEABSEN (HANYA BACA):
Data Peserta Didik (${dbData.students.length} orang):
${studentLines}

Data Tenaga Pengajar / Guru (${dbData.teachers.length} orang):
${teacherLines}
`;
          }

          systemPrompt = `Kamu adalah "FaceAbsen AI", asisten virtual untuk sistem presensi sekolah berbasis AI (Teachable Machine Face Recognition).

Pengguna saat ini: ${userName} (${roleLabel})

ATURAN PALING UTAMA & KETAT:
1. DILARANG KERAS MELAKUKAN CODING!
   - Tolak langsung jika meminta coding: "${CODING_REFUSAL_MESSAGE}"
2. JAWAB HANYA SESUAI PERTANYAAN DAN JANGAN MEMBUAT KOMENTAR APAPUN:
   - Jawab langsung ke inti pertanyaan tanpa basa-basi, salam, kalimat pembuka, kalimat penutup, komentar, opini, evaluasi, atau saran yang tidak diminta.
   - Jangan memberikan informasi di luar yang ditanyakan.
3. TOPIK YANG BOLEH DIJAWAB (HANYA INI SAJA):
   a. Data Peserta Didik (Siswa): Membaca dan menginformasikan nama, NIS, kelas, dan status pendaftaran model wajah berdasarkan DATA RESMI DATABASE di bawah.
   b. Data Tenaga Pengajar (Guru): Membaca dan menginformasikan nama guru dan NIP berdasarkan DATA RESMI DATABASE di bawah.
   c. Sistem presensi FaceAbsen:
      - Jam presensi: Masuk mulai 06:30 WIB, batas tepat waktu 07:15 WIB (lewat = Terlambat), pulang 16:00 WIB.
      - Cara presensi wajah: Klik "Buka FaceAbsen" → izinkan kamera → posisikan wajah di dalam bingkai oval → sistem AI mencatat otomatis.
      - Kendala kamera/wajah: Cek izin kamera browser, pastikan pencahayaan cukup dan wajah tidak terhalang masker/topi, atau hubungi admin jika model wajah belum terdaftar.
      - Prosedur tidak hadir (sakit/izin): Lapor ke wali kelas atau admin presensi.
      - Riwayat absensi & rekap kehadiran rombel guru.
4. SELAIN TOPIK DI ATAS JANGAN BAHAS APAPUN. Tolak dengan: "Maaf, saya hanya bisa membantu seputar sistem presensi FaceAbsen serta data peserta didik dan tenaga pengajar."
5. ATURAN IDENTITAS:
   - Jika pengguna menanyakan "siapa saya" atau variasinya: Jawab dengan menyebut nama pengguna dan status/jabatannya: "Anda adalah ${userName}, dengan jabatan sebagai ${roleLabel}."
   - Jika pengguna menanyakan "siapa anda" atau variasinya: Jawab dengan menyebut diri chatbot: "Saya adalah FaceAbsen AI, asisten virtual untuk sistem presensi sekolah FaceAbsen."
${dbContext}`;
        }

        const messages = [{ role: 'system', content: systemPrompt }];

        // Masukkan riwayat percakapan sebelumnya
        const recentHistory = clientHistory.slice(-4);
        for (const item of recentHistory) {
          if (item && (item.role === 'user' || item.role === 'assistant') && typeof item.content === 'string') {
            messages.push({
              role: item.role,
              content: item.content
            });
          }
        }

        // Masukkan pesan terbaru pengguna
        messages.push({
          role: 'user',
          content: userMessage
        });

        // Ambil info kunci aktif (Google Gemini vs NVIDIA NIM)
        const keyInfo = await getActiveApiKeyInfo();
        let result;
        try {
          if (keyInfo.isGemini) {
            result = await queryGeminiAI(clientHistory, userMessage, systemPrompt, keyInfo.apiKey);
          } else {
            result = await queryNvidiaAI(messages, MODEL_NAME, keyInfo.apiKey);
          }
          return sendJson(res, 200, {
            success: true,
            reply: result.reply,
            model: result.modelUsed || (keyInfo.isGemini ? 'Google Gemini' : MODEL_NAME)
          });
        } catch (apiErr) {
          console.warn('[WARN] Primary AI API error:', apiErr.message);

          // Coba fallback silang antar provider jika ada kunci alternatif
          if (keyInfo.isGemini && NVIDIA_API_KEY && !NVIDIA_API_KEY.startsWith('AQ.')) {
            try {
              result = await queryNvidiaAI(messages, FALLBACK_MODEL, NVIDIA_API_KEY);
              return sendJson(res, 200, {
                success: true,
                reply: result.reply,
                model: result.modelUsed || FALLBACK_MODEL
              });
            } catch (e2) {}
          } else if (!keyInfo.isGemini) {
            try {
              const conn = await mysql.createConnection(DB_CONFIG);
              const [gRows] = await conn.query("SELECT api_key FROM api_keys WHERE (provider LIKE '%gemini%' OR provider LIKE '%google%' OR api_key LIKE 'AQ.%' OR api_key LIKE 'AIza%') LIMIT 1");
              await conn.end();
              if (gRows && gRows.length > 0 && gRows[0].api_key) {
                result = await queryGeminiAI(clientHistory, userMessage, systemPrompt, gRows[0].api_key);
                return sendJson(res, 200, {
                  success: true,
                  reply: result.reply,
                  model: result.modelUsed || 'Google Gemini'
                });
              }
            } catch (eGem) {}
          }

          const directReply = getDirectDatabaseFallbackResponse(userMessage, dbData, isSuperUser, userName, userRole, clientHistory);
          if (directReply) {
            return sendJson(res, 200, {
              success: true,
              reply: directReply,
              model: 'FaceAbsen DB Engine (Offline Fallback)'
            });
          }
          throw apiErr;
        }

      } catch (err) {
        console.error('Chat processing error:', err);
        return sendJson(res, 500, {
          success: false,
          error: err.message || 'Terjadi kesalahan saat memproses pesan.'
        });
      }
    });
    return;
  }

  // 404 Route Not Found
  return sendJson(res, 404, {
    success: false,
    error: 'Endpoint tidak ditemukan.'
  });
});

// Start Server
server.listen(PORT, '0.0.0.0', async () => {
  const activeInfo = await getActiveApiKeyInfo().catch(() => null);
  const activeProvider = activeInfo ? activeInfo.provider : 'Auto-Detect';
  const activeModel = activeInfo && activeInfo.isGemini ? 'Google Gemini (gemini-3.5-flash)' : MODEL_NAME;
  console.log(`====================================================`);
  console.log(`🤖 FaceAbsen Chatbot AI Server is running!`);
  console.log(`📡 URL: http://localhost:${PORT}`);
  console.log(`⚡ Model: ${activeModel}`);
  console.log(`🔑 Provider: ${activeProvider}`);
  console.log(`💾 Database Access: Enabled (Analytics & Master Data)`);
  console.log(`🚫 Coding Allowed: False`);
  console.log(`====================================================`);
});

// Handle graceful shutdown
process.on('SIGINT', () => {
  console.log('\nMenutup Chatbot Server...');
  server.close(() => {
    console.log('Chatbot Server berhenti.');
    process.exit(0);
  });
});
