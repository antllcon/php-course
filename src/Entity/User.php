<?php
declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class User implements PasswordAuthenticatedUserInterface
{
    public function __construct(
        private ?int              $id = null,
        private string            $firstName,
        private string            $lastName,
        private ?string           $middleName,
        private string            $gender,
        private DateTimeImmutable $birthDate,
        private string            $email,
        private ?string           $phone,
        private ?string           $avatarPath,
        private string            $password,
        private array             $roles
    )
    {
        $this->roles = empty($roles) ? [UserRole::USER] : $roles;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getMiddleName(): ?string
    {
        return $this->middleName;
    }

    public function setMiddleName(?string $middleName): void
    {
        $this->middleName = $middleName;
    }

    public function getGender(): string
    {
        return $this->gender;
    }

    public function setGender(string $gender): void
    {
        $this->gender = $gender;
    }

    public function getBirthDate(): DateTimeImmutable
    {
        return $this->birthDate;
    }

    public function setBirthDate(DateTimeImmutable $birthDate): void
    {
        $this->birthDate = $birthDate;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
    }

    public function getAvatarPath(): ?string
    {
        return $this->avatarPath;
    }

    public function setAvatarPath(?string $avatarPath): void
    {
        $this->avatarPath = $avatarPath;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): void
    {
        foreach ($roles as $role) {
            if (!UserRole::isValid($role)) {
                throw new UnsupportedUserException(sprintf('"%s" is not a supported role', $role));
            }
        }

        $this->roles = array_unique(array_merge($roles, [UserRole::USER]));
    }


    public function eraseCredentials(): void
    {
        // $this->password = null;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function toArray(): array
    {
        return [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'middle_name' => $this->middleName,
            'gender' => $this->gender,
            'birth_date' => $this->birthDate->format('Y-m-d H:i:s'),
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar_path' => $this->avatarPath,
        ];
    }
}
