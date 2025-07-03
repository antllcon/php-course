<?php

use App\User\Controller\UserController;

require_once "vendor/autoload.php";

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$userController = new UserController();

switch ($path) {
    case '/':
        header('Location: /register');
        exit;

    case '/register':
        $userController->index();
        break;

    case '/register/save':
        try {
            $userController->register();
        } catch (Exception $e) {
            echo "Registration error: " . $e->getMessage();
        }
        break;

    case (bool)preg_match('#^/user/(\d+)$#', $path, $matches):
        $userId = (int)$matches[1];
        $userController->show($userId);
        break;

    default:
        http_response_code(404);
        echo '404 Not Found';
}