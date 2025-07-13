<?php
declare(strict_types=1);

namespace App\Service\UserValidator;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserNormalizer\UserNormalizerInterface;
use InvalidArgumentException;
use RuntimeException;

class UserValidator implements UserValidatorInterface
{
    private const REQUIRED_FIELDS = [
        'first_name',
        'last_name',
        'gender',
        'birth_date',
        'email',
        'password',
        'roles'
    ];
    private const ALLOWED_FIELDS = [
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

    public function __construct(private readonly UserRepository          $userRepository,
                                private readonly UserNormalizerInterface $userNormalizer)
    {
    }

    public function validateRequiredFields(array $userData): void
    {
        $missingFields = array_filter(self::REQUIRED_FIELDS, fn($field) => empty($userData[$field]));

        if ($missingFields) {
            throw new InvalidArgumentException(
                'Required fields are not specified: ' . implode(', ', $missingFields)
            );
        }
    }

    public function validateUniqueFields(string $email, ?string $phone, ?int $currentUserId = null): void
    {
        $existingUserByEmail = $this->userRepository->findByEmail($email);
        if (!is_null($existingUserByEmail) && $existingUserByEmail->getId() !== $currentUserId) {
            throw new InvalidArgumentException("User with email $email already exists");
        }

        if (!empty($phone)) {
            $normalizedPhone = $this->userNormalizer->normalizePhone($phone);
            $existingUserByPhone = $this->userRepository->findByPhone($normalizedPhone);
            if (!is_null($existingUserByPhone) && $existingUserByPhone->getId() !== $currentUserId) {
                throw new InvalidArgumentException("User with phone $phone already exists");
            }
        }
    }


    public function updateAllowedFields(User $user, array $data): void
    {
        foreach ($data as $field => $value) {
            if (!in_array($field, self::ALLOWED_FIELDS, true)) {
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
                $value = $this->userNormalizer->formatBirthDate($value);
            }

            if ($field === 'phone' && empty($value)) {
                $value = null;
            }

            $user->{$setter}($value);
        }
    }

    public function isValidPassword(string $password, string $confirmPassword): bool
    {
        if ($plainPassword !== $passwordConfirm) {
            throw new RuntimeException('Passwords do not match');
        }

        return true;
    }
}
