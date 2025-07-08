<?php
declare(strict_types=1);

namespace App\Model;

use App\Model\Entity\User;
use InvalidArgumentException;
use PDO;
use PDOException;
use RuntimeException;

class UserTable
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function create(User $user): int
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
            $stmt->execute($user->toArray());
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

            throw new RuntimeException("Failed to create user: " . $e->getMessage());
        }
    }

    public function read(int $userId): ?User
    {

        $sql = "SELECT
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
        WHERE `user_id` = :user_id";
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

    /**
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function update(User $user): void
    {
        try {

            $sql = "UPDATE user SET
                first_name = :first_name,
                last_name = :last_name,
                middle_name = :middle_name,
                gender = :gender,
                birth_date = :birth_date,
                email = :email,
                phone = :phone,
                avatar_path = :avatar_path
                WHERE user_id = :user_id";
            $stmt = $this->pdo->prepare($sql);
            $dataToExecute = $user->toArray();
            $dataToExecute['user_id'] = $user->getId();
            $stmt->execute($dataToExecute);

        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry')) {

                if (str_contains($e->getMessage(), 'email_idx')) {
                    throw new InvalidArgumentException(' Email id already exists');
                }

                if (str_contains($e->getMessage(), 'phone_idx')) {
                    throw new InvalidArgumentException('Phone id already exists');
                }
            }

            throw new RuntimeException("Failed to update user: " . $e->getMessage());
        }
    }

    public function delete(int $userId): void
    {

        $sql = "DELETE FROM `user` WHERE `user_id` = :user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
    }

    public function getAllUsers(): array
    {
        $sql = "SELECT
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
        ORDER BY `last_name`, `first_name`";

        $stmt = $this->pdo->query($sql);
        $users = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $users[] = new User(
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
        return $users;
    }
}
