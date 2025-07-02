<?php

namespace Controller;

use Exception;
use JetBrains\PhpStorm\NoReturn;
use Model\UserModel;

class AppController
{
    public function __construct()
    {
    }

    public function index(): void
    {
        require_once __DIR__ . '/../View/register_form.php';
    }

    /**
     * @throws Exception
     */
    #[NoReturn] public function register(): void
    {
        $userData = [
            'first_name' => $_POST['first_name'] ?? '',
            'last_name' => $_POST['last_name'] ?? '',
            'middle_name' => $_POST['middle_name'] ?? '',
            'gender' => $_POST['gender'] ?? '',
            'birth_date' => $_POST['birth_date'] ?? '',
            'email' => $_POST['email'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'avatar_path' => self::getPath(),
        ];

        $pdo = connectDatabase();
        $userModel = new UserModel($pdo);
        $userId = $userModel->save($userData);

        header("Location: /user/" . $userId);
        exit();
    }

    private static function getPath(): ?string
    {
        $avatarPath = null;

        if (!empty($_FILES['avatar']['tmp_name'])) {
            $uploadsDir = __DIR__ . '/../../../public/uploads/';
            $filename = uniqid() . '_' . basename($_FILES['avatar']['name']);
            $destination = $uploadsDir . $filename;
            move_uploaded_file($_FILES['avatar']['tmp_name'], $destination);
            $avatarPath = '/uploads/' . $filename;
        }

        return $avatarPath;
    }
}

