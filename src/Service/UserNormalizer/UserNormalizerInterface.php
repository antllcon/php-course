<?php
declare(strict_types=1);

namespace App\Service\UserNormalizer;

use DateTimeImmutable;
use InvalidArgumentException;

interface UserNormalizerInterface
{
    /**
     * @param array $userData
     * @throws InvalidArgumentException
     */
    public function normalize(array $userData): array;

    /**
     * @param mixed $birthDate может быть строкой, DateTime или DateTimeImmutable
     * @throws InvalidArgumentException
     */
    public function formatBirthDate(mixed $birthDate): DateTimeImmutable;

    public function normalizePhone(string $phone): string;
}
