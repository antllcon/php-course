<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use PDO;
use PDOException;
use RuntimeException;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
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
    public function create(User $user): int
    {
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        return $user->getId();
    }

    public function read(int $userId): ?User
    {
        return $this->find($userId);
    }

    /**
     * @throws Exception
     */
    public function update(User $user): void
    {
        $this->entityManager->flush();
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
    public function getAllUsers(): array
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
