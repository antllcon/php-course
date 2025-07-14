<?php
declare(strict_types=1);

namespace App\Security;

use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

readonly class UserProvider implements UserProviderInterface
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $appUser = $this->userRepository->findByEmail($identifier);

        if (is_null($appUser)) {
            $e = new UserNotFoundException('No user found for email ' . $identifier);
            $e->setUserIdentifier($identifier);
            throw $e;
        }

        return new SecurityUser($appUser);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof SecurityUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported', get_class($user)));
        }

        $reloadedUserEntity = $this->userRepository->findByEmail($user->getUserIdentifier());

        if (is_null($reloadedUserEntity)) {
            throw new UserNotFoundException(sprintf('User with ID "%s" not found', $user->getUser()->getId()));
        }

        return new SecurityUser($reloadedUserEntity);
    }

    public function supportsClass(string $class): bool
    {
        return SecurityUser::class === $class || is_subclass_of($class, SecurityUser::class);
    }
}
