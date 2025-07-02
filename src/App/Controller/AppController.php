<?php

namespace Controller;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use Model\UserTable;
use src\Entity\User;
use src\helper\UserHelper;
require_once __DIR__ . '/../../Entity/User.php';

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
        $userModel = new UserTable($pdo);
        UserHelper::validateRequiredFields($userData);
        $normalizedData = UserHelper::normalizeUserData($userData);

        $user = new User(
            id: null,
            firstName: $normalizedData['first_name'],
            lastName: $normalizedData['last_name'],
            middleName: $normalizedData['middle_name'],
            gender: $normalizedData['gender'],
            birthDate: $normalizedData['birth_date'],
            email: $normalizedData['email'],
            phone: $normalizedData['phone'],
            avatarPath: $normalizedData['avatar_path']
        );
        $userId = $userModel->save($user);

        header("Location: /user/" . $userId);
        exit();
    }

    private static function getPath(): ?string
    {
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $uploadsDir = __DIR__ . '/../../../public/uploads/';
        $filename = uniqid() . '_' . basename($_FILES['avatar']['name']);
        $destination = $uploadsDir . $filename;

        if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $destination)) {
            throw new \RuntimeException('Save failed');
        }

        return '/uploads/' . $filename;
    }
}

