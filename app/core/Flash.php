<?php
/**
 * Flash Message Notification Helper
 */
class Flash {

    public static function set($type, $message) {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        $_SESSION['flash'] = [
            'type' => $type, // success, danger, warning, info
            'message' => $message
        ];
    }

    public static function get() {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }

    public static function display() {
        $flash = self::get();
        if ($flash) {
            $type = htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8');
            $message = htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8');
            
            $icon = 'bi-info-circle-fill';
            if ($type === 'success') $icon = 'bi-check-circle-fill';
            if ($type === 'danger') $icon = 'bi-exclamation-triangle-fill';
            if ($type === 'warning') $icon = 'bi-exclamation-circle-fill';

            echo '
            <div class="alert alert-' . $type . ' alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm rounded-3 py-2 px-3 mb-3" role="alert">
                <i class="bi ' . $icon . ' flex-shrink-0 fs-5"></i>
                <div class="small fw-medium">' . $message . '</div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
        }
    }
}
