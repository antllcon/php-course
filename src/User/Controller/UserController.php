<?php
declare(strict_types=1);

namespace App\User\Controller;

use App\Connection\Database;
use App\User\Model\Entity\User;
use App\User\Model\UserTable;
use DateTime;
use Exception;
use InvalidArgumentException;
use JetBrains\PhpStorm\NoReturn;
use RuntimeException;

class UserController
{
    private const USER_REQUIRED_FIELDS = [
        'first_name',
        'last_name',
        'gender',
        'birth_date',
        'email'
    ];

    public function __construct()
    {
    }

    public function index(): void
    {
        require_once __DIR__ . '/../View/register_form.php';
    }

    public function show(int $userId): void
    {
        $pdo = Database::getConnection();
        $userModel = new UserTable($pdo);
        $user = $userModel->find($userId);

        if (!$user) {
            http_response_code(404);
            echo 'User not found';
            return;
        }

        require_once __DIR__ . '/../View/show_user.php';
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

        $pdo = Database::getConnection();
        $userModel = new UserTable($pdo);
        self::validateRequiredFields($userData);
        $normalizedData = self::normalizeUserData($userData);

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
            throw new RuntimeException('Save failed');
        }

        return '/uploads/' . $filename;
    }

    private static function validateRequiredFields(array $userParams): void
    {
        $missingFields = array_filter(self::USER_REQUIRED_FIELDS, fn($field) => empty($userParams[$field]));

        if ($missingFields) {
            throw new InvalidArgumentException(
                'Required fields are not specified: ' . implode(', ', $missingFields)
            );
        }
    }

    /**
     * @param array{
     *     first_name: string,
     *     last_name: string,
     *     middle_name?: string,
     *     gender: string,
     *     birth_date: string,
     *     email: string,
     *     phone?: string,
     *     avatar_path?: string
     * } $userData Входные данные пользователя.
     *
     * @return array{
     *     first_name: string,
     *     last_name: string,
     *     middle_name: string,
     *     gender: string,
     *     birth_date: string,
     *     email: string,
     *     phone: string,
     *     avatar_path: string
     * }
     *
     * @throws Exception
     */
    private static function normalizeUserData(array $userData): array
    {
        return [
            'first_name' => trim($userData['first_name']),
            'last_name' => trim($userData['last_name']),
            'middle_name' => isset($userData['middle_name']) ? trim($userData['middle_name']) : '',
            'gender' => $userData['gender'],
            'birth_date' => self::formatBirthDate($userData['birth_date']),
            'email' => strtolower(trim($userData['email'])),
            'phone' => isset($userData['phone']) ? self::normalizePhone($userData['phone']) : '',
            'avatar_path' => $userData['avatar_path'] ?? '',
        ];
    }

    /**
     * @throws Exception
     */
    private static function formatBirthDate($birthDate): string
    {
        if ($birthDate instanceof DateTime) {
            return $birthDate->format('Y-m-d H:i:s');
        }

        try {
            return (new DateTime($birthDate))->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            throw new InvalidArgumentException('Invalid birth date: ' . $e->getMessage());
        }
    }

    private static function normalizePhone(string $phone): string
    {
        return preg_replace('/[^0-9+]/', '', $phone);
    }
}