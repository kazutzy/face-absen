<?php
/**
 * Base Controller Class
 */
abstract class Controller {

    // Load Model
    public function model($model) {
        $modelFile = __DIR__ . '/../models/' . $model . '.php';
        if (file_exists($modelFile)) {
            require_once $modelFile;
            return new $model();
        }
        throw new Exception("Model {$model} tidak ditemukan.");
    }

    // Load View with Reusable Layout
    public function view($view, $data = [], $layout = 'default') {
        // Extract data ke variabel lokal
        extract($data);

        $viewFile = __DIR__ . '/../views/' . $view . '.php';

        if (!file_exists($viewFile)) {
            throw new Exception("View {$view} tidak ditemukan.");
        }

        if ($layout === 'default') {
            require __DIR__ . '/../views/layouts/header.php';
            require __DIR__ . '/../views/layouts/navbar.php';
            require $viewFile;
            require __DIR__ . '/../views/layouts/footer.php';
        } elseif ($layout === 'blank') {
            require __DIR__ . '/../views/layouts/header.php';
            require $viewFile;
            require __DIR__ . '/../views/layouts/footer.php';
        } else {
            require $viewFile;
        }
    }

    // JSON Response helper (untuk API / Teachable Machine AJAX)
    public function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    // Redirect helper
    public function redirect($url) {
        header('Location: ' . BASE_URL . ltrim($url, '/'));
        exit;
    }
}
