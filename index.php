<?php

use Controller\AppController;
use Controller\UserController;

require_once __DIR__ . '/src/App/Controller/UserController.php';
require_once __DIR__ . '/src/App/Controller/AppController.php';

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

switch ($path) {
    case '/':
        header('Location: /register');
        exit;

    case '/register':
        (new AppController())->index();
        break;

    case '/register/save':
        try {
            (new AppController())->register();
        } catch (Exception $e) {
            echo "Registration error: " . $e->getMessage();
        }
        break;

    case (bool)preg_match('#^/user/(\d+)$#', $path, $matches):
        $userId = (int)$matches[1];
        (new UserController())->show($userId);
        break;

    default:
        http_response_code(404);
        echo '404 Not Found';
}