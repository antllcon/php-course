<?php
declare(strict_types=1);

namespace App\Connection;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $instance = null;

    private function __construct()
    {
    }

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $config = self::getConnectionParams();

            try {
                self::$instance = new PDO(
                    "mysql:host={$config['host']};dbname={$config['name']};charset=utf8mb4",
                    $config['user'],
                    $config['password'],
                    $config['options']
                );
            } catch (PDOException $e) {
                throw new PDOException("Connection failed: " . $e->getMessage());
            }
        }

        return self::$instance;
    }

    /**
     * @return array{
     *     host: string,
     *     name: string,
     *     user: string,
     *     password: string,
     *     options: array
     * }
     */
    private static function getConnectionParams(): array
    {
        static $params = null;

        if ($params === null) {
            $params = require __DIR__ . '/../../config/database.php';
        }

        return $params;
    }
}