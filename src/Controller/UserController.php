<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use DateTime;
use DateTimeImmutable;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
        private readonly UserRepository $userTable
    )
    {
    }

    public function root(): Response
    {
        return $this->redirectToRoute(route: 'user_list');
    }

    public function listUsers(): Response
    {
        try {
            $users = $this->userTable->listAll();

            return $this->render('user/list_users.html.twig',
                ['users' => $users]
            );

        } catch (Exception $e) {
            error_log(message: "Error in list users: " . $e->getMessage());

            return new Response(
                'Server error: ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function registerForm(): Response
    {
        return $this->render('user/register_form.html.twig');
    }

    /**
     * @param Request $request
     * @return Response
     * @throws RuntimeException|InvalidArgumentException Если входные данные невалидны или загрузка файла не удалась.
     * @throws Exception Если произошла ошибка сервера во время регистрации.
     */
    public function registerUser(Request $request): Response
    {
        try {
            // Получение данных
            /** @var UploadedFile|null $avatarFile */
            $avatarFile = $request->files->get('avatar');
            $avatarPath = $this->processUploadedAvatar($avatarFile);
            $postData = $request->request->all();
            $userData = $this->getUserInput($postData, $avatarPath);

            // Валидация данных
            $this->validateRequiredFields($userData);
            $normalizedData = $this->normalizeUserData($userData);
            $this->validateUniqueUserFields($normalizedData['email'], $normalizedData['phone']);

            // Создание и сохранение пользователя
            $user = $this->createUserEntity($normalizedData);
            $this->userTable->store($user);

            return $this->redirectToRoute('user_show',
                ['id' => $user->getId()]
            );

        } catch (RuntimeException|InvalidArgumentException $e) {
            return self::render('user/register_form.html.twig', [
                'error' => $e->getMessage(),
                'old_input' => $request->request->all(),
            ], new Response('', Response::HTTP_BAD_REQUEST));

        } catch (Exception $e) {
            error_log("Error in register user: " . $e->getMessage());
            return new Response('Server error during registration: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param int $id
     * @return Response
     * @throws NotFoundHttpException Если пользователь не найден.
     * @throws Exception Если произошла ошибка сервера.
     */
    public function showUser(int $id): Response
    {
        try {
            $user = $this->findUser($id);
            return $this->render('user/show_user.html.twig', [
                'user' => $user,
            ]);

        } catch (InvalidArgumentException $e) {
            throw $this->createNotFoundException($e->getMessage());

        } catch (Exception $e) {
            error_log("Error in show user for ID $id: " . $e->getMessage());
            return new Response('Server error: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param int $id
     * @param Request $request
     * @return Response
     * @throws NotFoundHttpException Если пользователь не найден.
     * @throws InvalidArgumentException Если входные данные невалидны.
     * @throws RuntimeException Если загрузка/удаление файла не удались.
     * @throws Exception Если произошла ошибка сервера.
     */
    public function editUser(int $id, Request $request): Response
    {
        try {
            $user = $this->findUser($id);

            if ($request->isMethod('GET')) {
                return $this->render('user/edit_user.html.twig', [
                    'user' => $user,
                    'error' => null,
                ]);

            } elseif ($request->isMethod('POST')) {
                // Загрузка аватарки
                $postData = $request->request->all();
                /** @var UploadedFile|null $avatarFile */
                $avatarFile = $request->files->get('avatar');
                $this->handleAvatarLogic($user, $postData, $avatarFile);

                // Обновление и валидация
                self::updateAllowedFields($user, $postData);
                self::validateRequiredFields($postData);
                self::validateUniqueUserFields($user->getEmail(), $user->getPhone(), $user->getId());
                $this->userTable->store($user);

                return $this->redirectToRoute('user_show', ['id' => $user->getId()]);
            }

        } catch (InvalidArgumentException $e) {
            $error = $e->getMessage();
            return $this->render('user/edit_user.html.twig', [
                'user' => $user,
                'error' => $error,
            ], new Response('', Response::HTTP_BAD_REQUEST));

        } catch (Exception $e) {
            error_log("Error in editUser for ID $id: " . $e->getMessage());
            return new Response('Server error during user edit: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new Response('Invalid request method', Response::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * @param int $id
     * @return Response
     * @throws NotFoundHttpException Если пользователь не найден.
     * @throws Exception Если произошла ошибка сервера.
     */
    public function deleteUser(int $id): Response
    {
        try {
            $user = $this->findUser($id);
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

    /**
     * Находит пользователя по ID или выбрасывает исключение.
     *
     * @param int $userId
     * @return User
     * @throws InvalidArgumentException Если пользователь не найден.
     */
    private function findUser(int $userId): User
    {
        $user = $this->userTable->findById($userId);

        if (is_null($user)) {
            throw new InvalidArgumentException("User with ID $userId not found");
        }

        return $user;
    }

    /**
     * Проверяет уникальность email и phone.
     *
     * @param string $email
     * @param string|null $phone
     * @param int|null $currentUserId ID пользователя, которого мы обновляем (для исключения его из проверки).
     * @return void
     * @throws InvalidArgumentException Если email или phone уже заняты.
     */
    private function validateUniqueUserFields(string $email, ?string $phone, ?int $currentUserId = null): void
    {
        $existingUserByEmail = $this->userTable->findByEmail($email);
        if (!is_null($existingUserByEmail) && $existingUserByEmail->getId() !== $currentUserId) {
            throw new InvalidArgumentException("User with email $email already exists");
        }

        if (!empty($phone)) {
            $normalizedPhone = $this->normalizePhone($phone);
            $existingUserByPhone = $this->userTable->findByPhone($normalizedPhone);
            if (!is_null($existingUserByPhone) && $existingUserByPhone->getId() !== $currentUserId) {
                throw new InvalidArgumentException("User with phone $phone already exists");
            }
        }
    }

    /**
     * Обрабатывает логику загрузки/удаления аватара при редактировании.
     *
     * @param User $user
     * @param array $postData Данные из POST-запроса (например, 'remove_avatar').
     * @param UploadedFile|null $avatarFile Загруженный файл аватара.
     * @return void
     */
    private function handleAvatarLogic(User $user, array $postData, ?UploadedFile $avatarFile): void
    {
        $isAvatarRemovalRequested = !empty($postData['remove_avatar']) && $postData['remove_avatar'] === '1';

        if ($isAvatarRemovalRequested) {
            $this->deleteAvatarFile($user->getAvatarPath());
            $user->setAvatarPath(null);
            return;
        }

        if ($avatarFile instanceof UploadedFile && $avatarFile->isValid()) {
            $newAvatarPath = $this->processUploadedAvatar($avatarFile);
            $this->deleteAvatarFile($user->getAvatarPath());
            $user->setAvatarPath($newAvatarPath);
        }
    }

    /**
     * Обрабатывает загруженный файл аватара, перемещает его и возвращает публичный путь.
     *
     * @param UploadedFile|null $file
     * @return string|null Путь к сохраненному аватару или null, если файл не был загружен.
     * @throws InvalidArgumentException Если файл слишком большой, некорректного типа.
     * @throws RuntimeException Если файл невалиден или не удалось переместить файл.
     */
    private function processUploadedAvatar(?UploadedFile $file): ?string
    {
        if (!$file) {
            return null;
        }

        if (!$file->isValid()) {
            throw new RuntimeException('An error occurred during file upload: ' . $file->getErrorMessage());
        }

        if ($file->getSize() > self::AVATAR_MAX_SIZE) {
            throw new InvalidArgumentException('File is too large. Maximum size is ' . (self::AVATAR_MAX_SIZE / (1024 * 1024)) . 'MB');
        }

        if (!in_array($file->getMimeType(), self::AVATAR_ALLOWED_MIME_TYPES, true)) {
            throw new InvalidArgumentException('Invalid file type. Only JPG, PNG, and GIF are allowed');
        }

        $filename = uniqid('avatar_', true) . '.' . $file->guessExtension();

        try {
            $file->move(self::AVATAR_UPLOAD_DIR, $filename);
        } catch (Exception $e) {
            throw new RuntimeException('Failed to save the uploaded file: ' . $e->getMessage());
        }

        return '/uploads/' . $filename;
    }

    /**
     * Удаляет файл аватара с сервера.
     *
     * @param string|null $avatarPath Путь к аватару в базе данных.
     * @return void
     */
    private function deleteAvatarFile(?string $avatarPath): void
    {
        if (!$avatarPath) {
            return;
        }

        $fullPath = self::AVATAR_UPLOAD_DIR . basename($avatarPath);

        if (file_exists($fullPath) && is_file($fullPath)) {
            unlink($fullPath);
        }
    }

    /**
     * Извлекает и форматирует необходимые входные данные пользователя из POST-данных и пути аватара.
     *
     * @param array $postData Сырые POST-данные.
     * @param string|null $avatarPath Путь к загруженному аватару.
     * @return array
     */
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

    /**
     * Создает новую сущность User на основе нормализованных данных.
     *
     * @param array $normalizedData Нормализованные данные пользователя.
     * @return User
     */
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

    /**
     * Обновляет разрешенные поля сущности User из массива данных.
     *
     * @param User $user Сущность пользователя для обновления.
     * @param array $data Массив данных для обновления.
     * @return void
     * @throws InvalidArgumentException Если поле не разрешено для обновления.
     */
    private function updateAllowedFields(User $user, array $data): void
    {
        foreach ($data as $field => $value) {
            if (!in_array($field, self::USER_ALLOWED_FIELDS, true)) {
                throw new InvalidArgumentException("Field '$field' is not allowed for update.");
            }

            $setter = 'set' . str_replace('_', '', ucwords($field, '_'));
            if (!method_exists($user, $setter)) {
                continue;
            }

            if ($field === 'avatar_path' || $field === 'remove_avatar') {
                continue;
            }

            if ($field === 'birth_date') {
                $value = $this->formatBirthDate($value);
            }

            if ($field === 'phone' && empty($value)) {
                $value = null;
            }

            $user->{$setter}($value);
        }
    }

    /**
     * Проверяет наличие обязательных полей во входных данных.
     *
     * @param array $userParams Входные параметры пользователя.
     * @return void
     * @throws InvalidArgumentException Если отсутствуют обязательные поля.
     */
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
     * Нормализует входные данные пользователя (удаляет пробелы, форматирует дату, нормализует телефон).
     *
     * @param array $userData
     * @return array
     */
    private function normalizeUserData(array $userData): array
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
     * Преобразует дату рождения в объект DateTimeImmutable.
     *
     * @param mixed $birthDate Дата рождения в виде строки, DateTime или DateTimeImmutable.
     * @return DateTimeImmutable
     * @throws InvalidArgumentException Если формат даты невалиден.
     */
    private function formatBirthDate(mixed $birthDate): DateTimeImmutable
    {
        if ($birthDate instanceof DateTimeImmutable) {
            return $birthDate;
        }

        if ($birthDate instanceof DateTime) {
            return DateTimeImmutable::createFromMutable($birthDate);
        }

        try {
            $date = DateTimeImmutable::createFromFormat('Y-m-d', (string)$birthDate);

            if (!$date) {
                throw new InvalidArgumentException('Invalid date format');
            }

            return $date->setTime(0, 0);

        } catch (Exception $e) {
            throw new InvalidArgumentException('Invalid birth date: ' . $e->getMessage());
        }
    }

    /**
     * Нормализует номер телефона, удаляя все символы, кроме цифр и '+'.
     *
     * @param string $phone
     * @return string
     */
    private function normalizePhone(string $phone): string
    {
        return preg_replace('/[^0-9+]/', '', $phone);
    }
}
