<?php
/**
 * FaceAbsen - Front Controller Entry Point
 */

// 1. Load Global Configurations
require_once __DIR__ . '/../config/config.php';

// 2. Inisialisasi Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. Load Core MVC Engine & Security Classes
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Csrf.php';
require_once __DIR__ . '/../app/core/Flash.php';
require_once __DIR__ . '/../app/core/App.php';

// 4. Inisialisasi Dispatcher / Router
$app = new App();
