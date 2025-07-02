<?php

namespace Model;
use Exception;
use PDO;

require_once __DIR__ . '/../../helper/Database.php';

class UserModel
{
    private PDO $pdo;
    public function __construct()
    {
        $this->pdo = connectDatabase();
    }

    /**
     * @throws Exception
     */
    public function save(array $userData): int
    {
        return saveUserToDatabase($this->pdo, $userData);
    }

    public function find(int $userId): ?array
    {
        return findUserInDatabase($this->pdo, $userId);
    }
}