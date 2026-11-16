<?php
/**
 * Core Application Routing Engine (URL Dispatcher)
 */
class App {
    protected $controller = 'HomeController';
    protected $method = 'index';
    protected $params = [];

    public function __construct() {
        $url = $this->parseUrl();

        // 1. Resolve Controller
        if (!empty($url[0])) {
            $controllerName = ucfirst($url[0]) . 'Controller';
            $controllerPath = __DIR__ . '/../controllers/' . $controllerName . '.php';

            if (file_exists($controllerPath)) {
                $this->controller = $controllerName;
                unset($url[0]);
            }
        }

        require_once __DIR__ . '/../controllers/' . $this->controller . '.php';
        $this->controller = new $this->controller;

        // 2. Resolve Method / Action
        if (isset($url[1])) {
            $normalizedMethod = str_replace('-', '_', $url[1]);
            if (method_exists($this->controller, $normalizedMethod)) {
                $this->method = $normalizedMethod;
                unset($url[1]);
            } elseif (method_exists($this->controller, $url[1])) {
                $this->method = $url[1];
                unset($url[1]);
            }
        }

        // 3. Resolve Params
        $this->params = $url ? array_values($url) : [];

        // 4. Dispatch Request
        call_user_func_array([$this->controller, $this->method], $this->params);
    }

    protected function parseUrl() {
        if (isset($_GET['url'])) {
            $url = rtrim($_GET['url'], '/');
            $url = filter_var($url, FILTER_SANITIZE_URL);
            return explode('/', $url);
        }

        // Fallback untuk parse PATH_INFO jika URL rewrite langsung
        $requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $baseDir = dirname($scriptName);

        if (strpos($requestUri, $baseDir) === 0) {
            $path = substr($requestUri, strlen($baseDir));
            $path = trim($path, '/');
            // Hapus index.php jika ada
            $path = preg_replace('/^index\.php\/?/', '', $path);
            if (!empty($path)) {
                return explode('/', filter_var($path, FILTER_SANITIZE_URL));
            }
        }

        return [];
    }
}
