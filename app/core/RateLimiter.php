<?php
/**
 * RateLimiter Helper Class
 * Melindungi endpoint sensitif (seperti presensi wajah & autentikasi) dari brute-force / spamming
 */
class RateLimiter {

    /**
     * Memeriksa apakah request masih dalam batas rate limit
     * @param string $key Identifier unik (misal: 'scan_record_' . $userId)
     * @param int $maxAttempts Jumlah percobaan maksimum yang diizinkan
     * @param int $decaySeconds Jendela waktu (detik)
     * @return bool True jika diizinkan, False jika limit terlampaui
     */
    public static function check($key, $maxAttempts = 15, $decaySeconds = 60) {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        $now = time();
        $rateKey = '_rate_limit_' . md5($key);

        if (!isset($_SESSION[$rateKey])) {
            $_SESSION[$rateKey] = [
                'attempts' => 1,
                'reset_at' => $now + $decaySeconds
            ];
            return true;
        }

        $rateData = $_SESSION[$rateKey];

        // Jika periode waktu limit telah usai, reset
        if ($now > $rateData['reset_at']) {
            $_SESSION[$rateKey] = [
                'attempts' => 1,
                'reset_at' => $now + $decaySeconds
            ];
            return true;
        }

        // Jika masih dalam periode, periksa jumlah attempts
        if ($rateData['attempts'] >= $maxAttempts) {
            return false; // Limit terlampaui
        }

        $_SESSION[$rateKey]['attempts']++;
        return true;
    }

    /**
     * Reset rate limit untuk key tertentu
     */
    public static function reset($key) {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        $rateKey = '_rate_limit_' . md5($key);
        unset($_SESSION[$rateKey]);
    }
}
