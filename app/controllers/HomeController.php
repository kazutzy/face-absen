<?php
/**
 * HomeController - Landing page & Dashboard overview
 */
require_once __DIR__ . '/../core/Controller.php';

class HomeController extends Controller {

    public function index() {
        $schoolName = 'FaceAbsen System';
        $stats = [
            'total_users' => 0,
            'total_classes' => 0,
            'status' => 'Pondasi MVC Berhasil Diinisialisasi'
        ];

        try {
            $settingModel = $this->model('Setting');
            $classesModel = $this->model('Classes');
            $userModel = $this->model('User');

            $schoolName = $settingModel->get('school_name', 'FaceAbsen System');
            $stats['total_users'] = $userModel->count();
            $stats['total_classes'] = $classesModel->count();
            $stats['status'] = 'Sistem & Database Aktif';
        } catch (Throwable $e) {
            $stats['status'] = 'Pondasi MVC Aktif (Database Menunggu Migrasi)';
        }

        $data = [
            'title' => 'Home - ' . APP_NAME,
            'school_name' => $schoolName,
            'stats' => $stats
        ];

        $this->view('home/index', $data);
    }
}
