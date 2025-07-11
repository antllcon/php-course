<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\AvatarUploader\AvatarUploaderInterface;
use App\Service\UserNormalizer\UserNormalizer;
use App\Service\UserValidator\UserValidator;
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
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly AvatarUploaderInterface $avatarUploader,
        private readonly UserNormalizer $userNormalizer,
        private readonly UserValidator $userValidator,
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
            $users = $this->userRepository->listAll();

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
     * @throws RuntimeException|InvalidArgumentException
     * @throws Exception
     */
    public function registerUser(Request $request): Response
    {
        try {
            // Получение данных
            /** @var UploadedFile|null $avatarFile */
            $avatarFile = $request->files->get('avatar');
            $avatarPath = $this->avatarUploader->upload($avatarFile);
            $postData = $request->request->all();
            $userData = $this->getUserInput($postData, $avatarPath);

            // Валидация данных
            $this->userValidator->validateRequiredFields($userData);
            $normalizedData = $this->userNormalizer->normalize($userData);
            $this->userValidator->validateUniqueFields($normalizedData['email'], $normalizedData['phone']);

            // Создание и сохранение пользователя
            $user = $this->createUserEntity($normalizedData);
            $this->userRepository->store($user);

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
     * @throws NotFoundHttpException
     * @throws Exception
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
     * @throws NotFoundHttpException
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @throws Exception
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
                $this->handleUserAvatarLogic($user, $postData, $avatarFile);

                // Обновление и валидация
                $this->userValidator->updateAllowedFields($user, $postData);
                $this->userValidator->validateRequiredFields($postData);
                $this->userValidator->validateUniqueFields($user->getEmail(), $user->getPhone(), $user->getId());
                $this->userRepository->store($user);

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
     * @throws NotFoundHttpException
     * @throws Exception
     */
    public function deleteUser(int $id): Response
    {
        try {
            $user = $this->findUser($id);
            $this->userRepository->delete($id);
            $this->avatarUploader->delete($user->getAvatarPath());

            return $this->redirectToRoute('user_list');

        } catch (InvalidArgumentException $e) {
            throw $this->createNotFoundException($e->getMessage());

        } catch (Exception $e) {
            error_log("Error in deleteUser for ID $id: " . $e->getMessage());
            return new Response('Server error during user deletion: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param int $userId
     * @return User
     * @throws InvalidArgumentException
     */
    private function findUser(int $userId): User
    {
        $user = $this->userRepository->findById($userId);

        if (is_null($user)) {
            throw new InvalidArgumentException("User with ID $userId not found");
        }

        return $user;
    }

    /**
     * @param array $postData
     * @param string|null $avatarPath
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
     * @param array $normalizedData
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
     * @param User $user
     * @param array $postData
     * @param UploadedFile|null $avatarFile
     */
    private function handleUserAvatarLogic(User $user, array $postData, ?UploadedFile $avatarFile): void
    {
        $isAvatarRemovalRequested = !empty($postData['remove_avatar']) && $postData['remove_avatar'] === '1';

        if ($isAvatarRemovalRequested) {
            $this->avatarUploader->delete($user->getAvatarPath());
            $user->setAvatarPath(null);
            return;
        }

        if ($avatarFile instanceof UploadedFile && $avatarFile->isValid()) {
            $newAvatarPath = $this->avatarUploader->upload($avatarFile);
            $this->avatarUploader->delete($user->getAvatarPath());
            $user->setAvatarPath($newAvatarPath);
        }
    }
}
