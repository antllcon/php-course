<?php

namespace src\helper;

use DateTime;
use Exception;
use InvalidArgumentException;

class UserHelper
{
    public static function validateRequiredFields(array $userParams): void
    {
        $missingFields = array_filter(USER_REQUIRED_FIELDS, fn($field) => empty($userParams[$field]));

        if ($missingFields) {
            throw new InvalidArgumentException(
                'Required fields are not specified: ' . implode(', ', $missingFields)
            );
        }
    }

    /**
     * @throws Exception
     */
    public static function normalizeUserData(array $userData): array
    {
        return [
            ':first_name' => trim($userData['first_name']),
            ':last_name' => trim($userData['last_name']),
            ':middle_name' => isset($userData['middle_name']) ? trim($userData['middle_name']) : null,
            ':gender' => $userData['gender'],
            ':birth_date' => self::formatBirthDate($userData['birth_date']),
            ':email' => strtolower(trim($userData['email'])),
            ':phone' => isset($userData['phone']) ? self::normalizePhone($userData['phone']) : null,
            ':avatar_path' => $userData['avatar_path'] ?? null
        ];
    }

    /**
     * @throws Exception
     */
    public static function formatBirthDate($birthDate): string
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

    public static function normalizePhone(string $phone): string
    {
        return preg_replace('/[^0-9+]/', '', $phone);
    }
}