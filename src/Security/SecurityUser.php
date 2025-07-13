<?php
declare(strict_types=1);

namespace App\Security;

use App\Entity\User;

use App\Entity\UserRole;
use LogicException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class SecurityUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    private User $user;

    public function __construct(User $user)
    {
        if (null === $user->getId()) {
            throw new LogicException('The User entity must have an ID');
        }

        $this->user = $user;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Тут уже есть гарантия роли USER
     */
    public function getRoles(): array
    {
        $roles = $this->user->getRoles();
        $roles[] = [UserRole::USER];

        return array_unique($roles);
    }

    public function getPassword(): string
    {
        return $this->user->getPassword();
    }

    public function getUserIdentifier(): string
    {
        return $this->user->getEmail();
    }

    public function eraseCredentials(): void
    {
    }
}
