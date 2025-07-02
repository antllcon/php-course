<?php

namespace Controller;

require_once __DIR__ . '/../Model/UserTable.php';
use Model\UserTable;

class UserController
{
    public function show(int $userId): void
    {
        $pdo = connectDatabase();
        $userModel = new UserTable($pdo);
        $user = $userModel->find($userId);

        if (!$user) {
            http_response_code(404);
            echo 'User not found';
            return;
        }

        require_once __DIR__ . '/../View/show_user.php';
    }
}