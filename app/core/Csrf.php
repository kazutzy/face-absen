<?php
/**
 * CSRF Protection Token Helper
 */
class Csrf {
    
    // Generate atau ambil CSRF Token aktif dari session
    public static function getToken() {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function token() {
        return self::getToken();
    }

    // Render HTML hidden input field
    public static function field() {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    // Alias untuk field()
    public static function tokenField() {
        return self::field();
    }

    // Validasi token dari request POST / GET
    public static function validate($token = null) {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        if ($token === null) {
            $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
        }
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}
