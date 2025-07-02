<?php

namespace Model;

use Exception;
use src\Entity\User;
use InvalidArgumentException;
use PDO;
use PDOException;

require_once __DIR__ . '/../../Helper/Database.php';

class UserTable
{

    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @throws Exception
     */
    public function save(User $user): int
    {
        try {

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
            $stmt->execute([
                ':first_name' => $user->getFirstName(),
                ':last_name' => $user->getLastName(),
                ':middle_name' => $user->getMiddleName(),
                ':gender' => $user->getGender(),
                ':birth_date' => $user->getBirthDate(),
                ':email' => $user->getEmail(),
                ':phone' => $user->getPhone(),
                ':avatar_path' => $user->getAvatarPath(),
            ]);

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

    public function find(int $userId): ?User
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
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return new User(
                id: (int)$row['user_id'],
                firstName: $row['first_name'],
                lastName: $row['last_name'],
                middleName: $row['middle_name'] !== '' ? $row['middle_name'] : null,
                gender: $row['gender'],
                birthDate: $row['birth_date'],
                email: $row['email'],
                phone: $row['phone'],
                avatarPath: $row['avatar_path'] !== '' ? $row['avatar_path'] : null
            );
        }

        return null;
    }
}