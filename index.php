<?php

require_once __DIR__ . '/config.php';

function connectDatabase(): PDO
{
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

    try {
        return new PDO($dsn, DB_USER, DB_PASSWORD, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Исключения при ошибках
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Ассоциативные массивы
            PDO::ATTR_EMULATE_PREPARES => false, // Нативные prepared statements
            PDO::ATTR_STRINGIFY_FETCHES => false, // Не конвертировать числа в строк
        ]);
    } catch (PDOException $e) {
        throw new PDOException("Connection failed: " . $e->getMessage());
    }
}

function saveUserToDatabase(PDO $pdo, array $userParams): int
{
    validateRequiredFields($userParams);
    $normalizedData = normalizeUserData($userParams);

    $sql = "INSERT INTO user (
                first_name, 
                last_name, 
                middle_name, 
                gender, 
                birth_date, 
                email, 
                phone, 
                avatar_path
            ) VALUES (
                :first_name, 
                :last_name, 
                :middle_name, 
                :gender, 
                :birth_date, 
                :email, 
                :phone, 
                :avatar_path
            )";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($normalizedData);
        return (int)$pdo->lastInsertId();
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'Duplicate entry')) {

            if (str_contains($e->getMessage(), 'email_idx')) {
                throw new InvalidArgumentException('Пользователь с таким email уже существует');
            }
            if (str_contains($e->getMessage(), 'phone_idx')) {
                throw new InvalidArgumentException('Пользователь с таким телефоном уже существует');
            }
        }
        throw $e;
    }
}

function validateRequiredFields(array $userParams): void
{
    $missingFields = array_filter(USER_REQUIRED_FIELDS, fn($field) => empty($userParams[$field]));

    if ($missingFields) {
        throw new InvalidArgumentException(
            'Required fields are not specified: ' . implode(', ', $missingFields)
        );
    }
}

function normalizeUserData(array $userData): array
{
    return [
        ':first_name' => trim($userData['first_name']),
        ':last_name' => trim($userData['last_name']),
        ':middle_name' => isset($userData['middle_name']) ? trim($userData['middle_name']) : null,
        ':gender' => $userData['gender'],
        ':birth_date' => formatBirthDate($userData['birth_date']),
        ':email' => strtolower(trim($userData['email'])),
        ':phone' => isset($userData['phone']) ? normalizePhone($userData['phone']) : null,
        ':avatar_path' => $userData['avatar_path'] ?? null
    ];
}

function formatBirthDate($birthDate): string
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

function normalizePhone(string $phone): string
{
    return preg_replace('/[^0-9+]/', '', $phone);
}