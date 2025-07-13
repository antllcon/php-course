<?php
declare(strict_types=1);

namespace App\Service\UserValidator;

use App\Entity\User;
use InvalidArgumentException;
use RuntimeException;

interface UserValidatorInterface
{
    /**
     * @param array $userData
     * @throws InvalidArgumentException
     */
    public function validateRequiredFields(array $userData): void;

    /**
     * @throws InvalidArgumentException
     */
    public function validateUniqueFields(string $email, ?string $phone, ?int $currentUserId = null): void;

    /**
     * @param array $data
     * @throws InvalidArgumentException
     */
    public function updateAllowedFields(User $user, array $data): void;

    /**
     * @return bool
     */
    public function isValidPassword(string $password, string $confirmPassword): bool;
}
