<?php

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/UserHelper.php';

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