<?php

use Controller\RegisterController;
use Controller\UserController;

require_once __DIR__ . '/src/App/Controller/UserController.php';
require_once __DIR__ . '/src/App/Controller/RegisterController.php';

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

switch ($path) {
    case '/register':
        (new RegisterController())->index();
        break;

    case '/register/save':
        try {
            (new RegisterController())->register();
        } catch (Exception $e) {
            echo "Registration error: " . $e->getMessage();
        }
        break;

    case (preg_match('#^/user/(\d+)$#', $path, $matches) ? true : false):
        $userId = (int)$matches[1];
        (new UserController())->show($userId);
        break;

    default:
        http_response_code(404);
        echo '404 Not Found';
}