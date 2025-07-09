<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\User;
use App\Model\UserTable;
use DateTime;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController extends AbstractController
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

    private const AVATAR_UPLOAD_DIR = __DIR__ . '/../../public/uploads/';
    private const AVATAR_MAX_SIZE = 5 * 1024 * 1024;
    private const AVATAR_ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/gif'];


    public function __construct(
        private readonly UserTable $userTable
    )
    {
    }

    public function root(): Response
    {
        return $this->redirectToRoute('user_list');
    }

    public function listUsers(): Response
    {
        try {
            $users = $this->userTable->getAllUsers();
            return $this->render('user/list_users.html.twig', [
                'users' => $users,
            ]);
        } catch (Exception $e) {
            error_log("Error in listUsers: " . $e->getMessage());
            return new Response('Server error: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function registerForm(): Response
    {
        return $this->render('user/register_form.html.twig');
    }

    public function registerUser(Request $request): Response
    {
        try {
            $avatarFile = $_FILES['avatar'] ?? null;
            $avatarPath = $this->processUploadedAvatar($avatarFile);
            $userData = $this->getUserInput($_POST, $avatarPath);
            self::validateRequiredFields($userData);
            $normalizedData = self::normalizeUserData($userData);
            $user = $this->createUserEntity($normalizedData);
            $userId = $this->userTable->create($user);
            return $this->redirectToRoute('user_show', ['id' => $userId]);

        } catch (RuntimeException|InvalidArgumentException $exception) {
            $error = $exception->getMessage();
            return $this->render('user/register_form.html.twig', [
                'error' => $error,
                'old_input' => $_POST,
            ], new Response('', Response::HTTP_BAD_REQUEST));
        } catch (Exception $e) {
            error_log("Error in registerUser: " . $e->getMessage());
            return new Response('Server error during registration: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function showUser(int $id): Response
    {
        try {
            $user = $this->getUserOrFail($id);
            return $this->render('user/show_user.html.twig', [
                'user' => $user,
            ]);
        } catch (InvalidArgumentException $e) {
            throw $this->createNotFoundException($e->getMessage());
        } catch (Exception $e) {
            error_log("Error in showUser for ID $id: " . $e->getMessage());
            return new Response('Server error: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function editUser(int $id, Request $request): Response
    {
        try {
            $user = $this->getUserOrFail($id);

            if ($request->isMethod('GET')) {
                return $this->render('user/edit_user.html.twig', [
                    'user' => $user,
                    'error' => null,
                ]);

            } elseif ($request->isMethod('POST')) {
                $this->handleAvatarLogic($user, $_POST, $_FILES);
                unset($_POST['avatar'], $_POST['remove_avatar']);

                $this->updateAllowedFields($user, $_POST);
                self::validateRequiredFields($_POST);
                $this->userTable->update($user);

                return $this->redirectToRoute('user_show', ['id' => $user->getId()]);
            }
        } catch (InvalidArgumentException $e) {
            $error = $e->getMessage();
            return $this->render('user/edit_user.html.twig', [
                'user' => $user,
                'error' => $error,
            ], new Response('', Response::HTTP_BAD_REQUEST));
        } catch (Exception $e) {
            error_log("Error in editUser for ID {$id}: " . $e->getMessage());
            return new Response('Server error during user edit: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        return new Response('Invalid request method', Response::HTTP_METHOD_NOT_ALLOWED);
    }

    public function deleteUser(int $id): Response
    {
        try {
            $user = $this->getUserOrFail($id);
            $this->userTable->delete($id);
            $this->deleteAvatarFile($user->getAvatarPath());

            return $this->redirectToRoute('user_list');
        } catch (InvalidArgumentException $e) {
            throw $this->createNotFoundException($e->getMessage());
        } catch (Exception $e) {
            error_log("Error in deleteUser for ID $id: " . $e->getMessage());
            return new Response('Server error during user deletion: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function getUserOrFail(int $userId): User
    {
        $user = $this->userTable->read($userId);

        if ($user === null) {
            throw new InvalidArgumentException("User with ID $userId not found.");
        }

        return $user;
    }

    private function handleAvatarLogic(User $user, array $postData, array $filesData): void
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
        if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('An error occurred during file upload.');
        }

        if ($file['size'] > self::AVATAR_MAX_SIZE) {
            throw new InvalidArgumentException('File is too large. Maximum size is ' . (self::AVATAR_MAX_SIZE / (1024 * 1024)) . 'MB.');
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

        // Строим полный путь к файлу
        $fullPath = self::AVATAR_UPLOAD_DIR . basename($avatarPath); // basename для безопасности

        if (file_exists($fullPath) && is_file($fullPath)) {
            unlink($fullPath);
        }
    }

    private function getUserInput(array $postData, ?string $avatarPath): array
    {
        return [
            'first_name' => $postData['first_name'] ?? '',
            'last_name' => $postData['last_name'] ?? '',
            'middle_name' => $postData['middle_name'] ?? '',
            'gender' => $postData['gender'] ?? '',
            'birth_date' => $postData['birth_date'] ?? '',
            'email' => $postData['email'] ?? '',
            'phone' => $postData['phone'] ?? '',
            'avatar_path' => $avatarPath,
        ];
    }

    private function createUserEntity(array $normalizedData): User
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

    private function updateAllowedFields(User $user, array $data): void
    {
        foreach ($data as $field => $value) {
            if (!in_array($field, self::USER_ALLOWED_FIELDS, true)) {
                throw new InvalidArgumentException("Field '$field' is not allowed for update.");
            }

            $setter = 'set' . str_replace('_', '', ucwords($field, '_'));
            if (!method_exists($user, $setter)) {
                throw new RuntimeException("Setter method $setter does not exist in User entity.");
            }

            if ($field === 'avatar_path' || $field === 'remove_avatar') {
                continue;
            }

            $user->{$setter}($value);
        }
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

    private static function formatBirthDate(mixed $birthDate): string
    {
        if ($birthDate instanceof DateTime) {
            return $birthDate->format('Y-m-d');
        }

        try {
            return (new DateTime((string)$birthDate))->format('Y-m-d');
        } catch (Exception $e) {
            throw new InvalidArgumentException('Invalid birth date: ' . $e->getMessage());
        }
    }

    private static function normalizePhone(string $phone): string
    {
        return preg_replace('/[^0-9+]/', '', $phone);
    }
}
