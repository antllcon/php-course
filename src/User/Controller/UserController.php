<?php
declare(strict_types=1);

namespace App\User\Controller;

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
    private const USER_ALLOWED_FIELDS = [
        'first_name',
        'last_name',
        'middle_name',
        'gender',
        'birth_date',
        'email',
        'phone',
        'avatar_path',
        'remove_avatar'
    ];

    private const AVATAR_UPLOAD_DIR = __DIR__ . '/../../../public/uploads/';
    private const AVATAR_MAX_SIZE = 5 * 1024 * 1024;
    private const AVATAR_ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/gif'];

    public function __construct(private UserTable $userTable)
    {
    }

    public function index(): void
    {
        require_once __DIR__ . '/../View/register_form.php';
    }

    public function showUser(int $userId): void
    {
        $user = $this->getUserOrFail($userId);
        require_once __DIR__ . '/../View/show_user.php';
    }

    /**
     * @param array<string, mixed> $data
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    #[NoReturn] public function editUser(int $userId, array $data, array $filesData): void
    {
        try {
            $user = $this->getUserOrFail($userId);
            self::handleAvatarLogic($user, $data, $filesData);
            unset($data['avatar'], $data['remove_avatar']);

            self::updateAllowedFields($user, $data);
            self::validateRequiredFields($data);
            $this->userTable->update($user);
            $this->redirectToUserProfile($userId);

        } catch (RuntimeException|InvalidArgumentException $exception) {
            $error = $exception->getMessage();
            $this->showEditForm($userId, $error);
            exit();
        }

    }

    #[NoReturn] public function deleteUser(int $userId): void
    {
        $this->getUserOrFail($userId);
        $this->userTable->delete($userId);
        self::redirectToUserList();
    }

    /**
     * @throws Exception
     */
    #[NoReturn] public function registerUser(): void
    {
        try {
            $avatarPath = $this->processUploadedAvatar($_FILES);
            $userData = self::getUserInput($avatarPath);
            self::validateRequiredFields($userData);
            $normalizedData = self::normalizeUserData($userData);
            $user = self::createUserEntity($normalizedData);
            $userId = $this->userTable->create($user);
            self::redirectToUserProfile($userId);
        } catch (RuntimeException|InvalidArgumentException $exception) {

            $error = $exception->getMessage();
            require_once __DIR__ . '/../View/register_form.php';
            exit();
        }

    }

    public function showEditForm(int $userId, ?string $error = null): void
    {
        $user = $this->getUserOrFail($userId);
        include __DIR__ . '/../View/edit_user.php';
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getUserOrFail(int $userId): User
    {
        $user = $this->userTable->read($userId);

        // is_null
        if ($user === null) {
            throw new InvalidArgumentException("User with ID $userId not found.");
        }

        return $user;
    }

    public function listUsers(): void
    {
        $users = $this->userTable->getAllUsers();
        require_once __DIR__ . '/../View/list_users.php';
    }

    private function handleAvatarLogic(User $user, array $data, array $filesData): void
    {
        $isAvatarRemovalRequested = !empty($postData['remove_avatar']) && $postData['remove_avatar'] === '1';

        if ($isAvatarRemovalRequested) {
            $this->deleteAvatarFile($user->getAvatarPath());
            $user->setAvatarPath(null);
            return;
        }

        $uploadedFile = $filesData['avatar'] ?? null;
        if ($uploadedFile && $uploadedFile['error'] === UPLOAD_ERR_OK) {
            $newAvatarPath = $this->processUploadedAvatar($uploadedFile);
            $this->deleteAvatarFile($user->getAvatarPath());
            $user->setAvatarPath($newAvatarPath);
        }
    }

    private function processUploadedAvatar(?array $file): ?string
    {
        $file = $_FILES['avatar'] ?? null;
        if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('An error occurred during file upload.');
        }

        if ($file['size'] > self::AVATAR_MAX_SIZE) {
            throw new InvalidArgumentException('File is too large. Maximum size is 5MB.');
        }

        $mimeType = mime_content_type($file['tmp_name']);
        if (!in_array($mimeType, self::AVATAR_ALLOWED_MIME_TYPES, true)) {
            throw new InvalidArgumentException('Invalid file type. Only JPG, PNG, and GIF are allowed.');
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('avatar_', true) . '.' . $extension;
        $destination = self::AVATAR_UPLOAD_DIR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new RuntimeException('Failed to save the uploaded file.');
        }

        return '/uploads/' . $filename;
    }

    private function deleteAvatarFile(?string $avatarPath): void
    {
        if (!$avatarPath) {
            return;
        }

        $fullPath = __DIR__ . '/../../../public' . $avatarPath;
        if (file_exists($fullPath) && is_file($fullPath)) {
            unlink($fullPath);
        }
    }

    /**
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    private
    function updateAllowedFields(User $user, array $data): void
    {
        foreach ($data as $field => $value) {
            if (!in_array($field, self::USER_ALLOWED_FIELDS, true)) {
                throw new InvalidArgumentException("Field '$field' is not allowed for update.");
            }

            // Устанавливаем новое значение (можно добавить валидацию)
            $setter = 'set' . str_replace('_', '', ucwords($field, '_'));
            if (!method_exists($user, $setter)) {
                throw new RuntimeException("Setter method $setter does not exist in User entity.");
            }

            $user->{$setter}($value);
        }
    }

    private
    function getUserInput(?string $avatarPath): array
    {
        return [
            'first_name' => $_POST['first_name'] ?? '',
            'last_name' => $_POST['last_name'] ?? '',
            'middle_name' => $_POST['middle_name'] ?? '',
            'gender' => $_POST['gender'] ?? '',
            'birth_date' => $_POST['birth_date'] ?? '',
            'email' => $_POST['email'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'avatar_path' => $avatarPath,
        ];
    }

    private
    function createUserEntity(array $normalizedData): User
    {
        return new User(
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
    }

    #[
        NoReturn] private function redirectToUserProfile(int $userId): void
    {
        header("Location: /user/" . $userId);
        exit();
    }

    #[NoReturn] private function redirectToRegister(): void
    {
        header("Location: /register");
        exit();
    }

    #[NoReturn] private function redirectToUserList(): void
    {
        header("Location: /users");
        exit();
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
            return $birthDate->format('Y-m-d');
        }

        try {
            return (new DateTime($birthDate))->format('Y-m-d');
        } catch (Exception $e) {
            throw new InvalidArgumentException('Invalid birth date: ' . $e->getMessage());
        }
    }

    private static function normalizePhone(string $phone): string
    {
        return preg_replace('/[^0-9+]/', '', $phone);
    }
}