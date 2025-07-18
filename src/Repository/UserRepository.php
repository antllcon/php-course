<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

class UserRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $entityManager;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
        $this->entityManager = $this->getEntityManager();
    }

    /**
     * @throws Exception
     */
    public function store(User $user): void
    {
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function findById(int $userId): ?User
    {
        return $this->find($userId);
    }

    public function delete(int $userId): void
    {
        $user = $this->find($userId);
        if ($user) {
            $this->entityManager->remove($user);
            $this->entityManager->flush();
        }
    }

    /**
     * @return User[]
     */
    public function listAll(): array
    {
        return $this->findBy([], ['lastName' => 'ASC', 'firstName' => 'ASC']);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function findByPhone(string $phone): ?User
    {
        if (empty($phone)) {
            return null;
        }
        return $this->findOneBy(['phone' => $phone]);
    }
}
