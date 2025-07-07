<?php
declare(strict_types=1);

use App\Connection\Database;
use App\User\Controller\UserController;
use App\User\Model\UserTable;

require_once "vendor/autoload.php";

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pdo = Database::getConnection();
$userModel = new UserTable($pdo);
$userController = new UserController($userModel);

try {

    switch ($path) {
        case '/':
            header('Location: /users');
            exit;

        case '/users':
            $userController->listUsers();
            break;

        case '/register':
            $userController->index();
            break;

        case '/register/save':
            $userController->registerUser();
            break;

        case (bool)preg_match('#^/user/(\d+)$#', $path, $matches):
            $userId = (int)$matches[1];
            $userController->showUser($userId);
            break;

        case (bool)preg_match('#^/user/(\d+)/edit$#', $path, $matches):

            error_log("Edit route matched for user ID: " . $matches[1]);

            $userId = (int)$matches[1];
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $userController->showEditForm($userId);
            } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $userController->editUser($userId, $_POST, $_FILES);;
            }
            break;

        case (bool)preg_match('#^/user/(\d+)/delete$#', $path, $matches):
            $userId = (int)$matches[1];
            $userController->deleteUser($userId);
            break;

        default:
            http_response_code(404);
            echo '404 Not Found';
    }
} catch (Exception $exception) {
    http_response_code(404);
    error_log("Error: " . $exception->getMessage());
    echo "Server error";
}