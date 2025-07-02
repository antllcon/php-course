<?php

namespace Model;

use Exception;
use InvalidArgumentException;
use PDO;
use PDOException;
use src\helper\UserHelper;

require_once __DIR__ . '/../../helper/Database.php';

class UserModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @throws Exception
     */
    public function save(array $userData): int
    {
        try {
            UserHelper::validateRequiredFields($userData);
            $normalizedData = UserHelper::normalizeUserData($userData);

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

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($normalizedData);
            return (int)$this->pdo->lastInsertId();

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

    public function find(int $userId): ?array
    {
        $sql = "
        SELECT 
            `user_id`,
            `first_name`, 
            `last_name`, 
            `middle_name`, 
            `gender`, 
            `birth_date`, 
            `email`, 
            `phone`, 
            `avatar_path`
        FROM `user`
        WHERE `user_id` = :user_id
    ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            return $user;
        }

        return null;
    }
}