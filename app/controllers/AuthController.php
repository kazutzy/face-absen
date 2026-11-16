<?php
/**
 * AuthController - User Authentication & Session Management
 */
require_once __DIR__ . '/../core/Controller.php';

class AuthController extends Controller {

    public function login() {
        // Jika sudah login, redirect langsung ke dashboard role-nya
        if (Auth::check()) {
            $this->redirect(Auth::getDashboardUrl());
        }

        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrfToken = $_POST['csrf_token'] ?? '';
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');

            // 1. Validasi CSRF Token
            if (!Csrf::validate($csrfToken)) {
                $error = 'Sesi form kedaluwarsa atau token CSRF tidak valid. Silakan coba lagi.';
            } 
            // 2. Validasi Input
            elseif (empty($username) || empty($password)) {
                $error = 'Username dan Password wajib diisi.';
            } else {
                try {
                    $userModel = $this->model('User');
                    $user = $userModel->verifyCredentials($username, $password);

                    if ($user) {
                        // Inisialisasi Auth Session
                        Auth::login($user);

                        Flash::set('success', 'Selamat datang kembali, ' . htmlspecialchars($user['name']) . '!');
                        $this->redirect(Auth::getDashboardUrl($user['role']));
                    } else {
                        $error = 'Username atau Password salah, atau akun Anda dinonaktifkan.';
                    }
                } catch (Throwable $e) {
                    $error = 'Terjadi kesalahan sistem database: ' . $e->getMessage();
                }
            }
        }

        $data = [
            'title' => 'Masuk - ' . APP_NAME,
            'error' => $error
        ];

        $this->view('auth/login', $data, 'blank');
    }

    public function logout() {
        Auth::logout();
        Flash::set('info', 'Anda telah berhasil keluar dari sistem.');
        $this->redirect('auth/login');
    }
}
