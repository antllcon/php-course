<?php

namespace Controller;

require_once __DIR__ . '/../Model/UserModel.php';
use Model\UserModel;

class UserController
{
    public function show(int $userId): void
    {
        $userModel = new UserModel();
        $user = $userModel->find($userId);

        if (!$user) {
            http_response_code(404);
            echo 'User not found';
            return;
        }

        // Написать
        require __DIR__ . '/../View/user_show.php';
    }
}
