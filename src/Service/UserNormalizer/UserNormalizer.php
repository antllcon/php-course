<?php
declare(strict_types=1);

namespace App\Service\UserNormalizer;

use DateTimeImmutable;
use DateTime;
use Exception;
use InvalidArgumentException;

class UserNormalizer implements UserNormalizerInterface
{
    public function normalize(array $userData): array
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
            'password' => $userData['password'],
            'roles' => $userData['roles']
        ];
    }

    public function formatBirthDate(mixed $birthDate): DateTimeImmutable
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

    public function normalizePhone(string $phone): string
    {
        return preg_replace('/[^0-9+]/', '', $phone);
    }
}
