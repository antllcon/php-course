<?php

namespace App\Entity;

final class UserRole
{
    public const USER = 'ROLE_USER';
    public const ADMIN = 'ROLE_ADMIN';
    private const ALL_ROLES = [
        self::USER,
        self::ADMIN,
    ];

    public static function isValid(string $role): bool
    {
        return in_array($role, self::ALL_ROLES, true);
    }

    public static function isAdmin(string $role): bool
    {
        return $role === self::ADMIN;
    }

    public static function getAllRoles(): array
    {
        return self::ALL_ROLES;
    }
}
