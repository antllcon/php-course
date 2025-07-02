<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helper/UserHelper.php';

use helper\UserHelper;

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

/**
 * @throws Exception
 */
function saveUserToDatabase(PDO $pdo, array $userParams): int
{
    try {
        UserHelper::validateRequiredFields($userParams);
        $normalizedData = UserHelper::normalizeUserData($userParams);

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

        $stmt = $pdo->prepare($sql);
        $stmt->execute($normalizedData);
        return (int)$pdo->lastInsertId();

    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'Duplicate entry')) {

            if (str_contains($e->getMessage(), 'email_idx')) {
                throw new InvalidArgumentException(' Email id already exists');
            }
            if (str_contains($e->getMessage(), 'phone_idx')) {
                throw new InvalidArgumentException('Phone id already exists');
            }
        }

        throw $e;
    }
}

$pdo = connectDatabase();

$testUser = [
    'first_name' => 'Степан',
    'last_name' => 'Глухарев',
    'gender' => 'male',
    'birth_date' => '2005-10-07',
    'email' => 'pokeomivan32@gmail.com',
    'phone' => '+79194186248'
];

try {
    $userId = saveUserToDatabase($pdo, $testUser);
    echo "New uer id: " . $userId;
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}