<?php
/**
 * Auth Helper & Middleware Guard (Role-Based Access Control)
 */
class Auth {

    // Cek apakah user sedang login
    public static function check() {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        return !empty($_SESSION['user']) && !empty($_SESSION['user']['id']);
    }

    // Ambil data user aktif
    public static function user() {
        if (self::check()) {
            return $_SESSION['user'];
        }
        return null;
    }

    public static function id() {
        $user = self::user();
        return $user ? (int)$user['id'] : null;
    }

    public static function role() {
        $user = self::user();
        return $user ? $user['role'] : null;
    }

    public static function name() {
        $user = self::user();
        return $user ? $user['name'] : '';
    }

    public static function username() {
        $user = self::user();
        return $user ? $user['username'] : '';
    }

    // Cek apakah user memiliki salah satu role yang diizinkan
    public static function hasRole($roles) {
        if (!self::check()) {
            return false;
        }

        $currentRole = self::role();

        if (is_array($roles)) {
            return in_array($currentRole, $roles, true);
        }

        return $currentRole === $roles;
    }

    // Middleware Guard: Wajib Login
    public static function requireLogin() {
        if (!self::check()) {
            Flash::set('warning', 'Silakan masuk terlebih dahulu untuk mengakses halaman ini.');
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }
    }

    // Middleware Guard: Wajib Role Tertentu
    public static function requireRole($roles) {
        self::requireLogin();

        if (!self::hasRole($roles)) {
            Flash::set('danger', 'Akses ditolak! Anda tidak memiliki izin untuk mengakses halaman tersebut.');
            $dashboardUrl = self::getDashboardUrl();
            header('Location: ' . BASE_URL . $dashboardUrl);
            exit;
        }
    }

    // Mengambil URL dashboard berdasarkan role pengguna
    public static function getDashboardUrl($role = null) {
        $role = $role ?? self::role();
        switch ($role) {
            case 'super_user':
                return 'admin/dashboard';
            case 'guru':
                return 'guru/dashboard';
            case 'peserta_didik':
                return 'siswa/dashboard';
            default:
                return '';
        }
    }

    // Proses login user & session regeneration
    public static function login($user) {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        // Regenerate session id untuk mencegah session fixation attack
        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            session_regenerate_id(true);
        }

        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'username' => $user['username'],
            'name' => $user['name'],
            'role' => $user['role'],
            'status' => $user['status'],
            'logged_at' => time()
        ];
    }

    // Proses logout
    public static function logout() {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        $_SESSION = [];

        if (!headers_sent() && ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}
