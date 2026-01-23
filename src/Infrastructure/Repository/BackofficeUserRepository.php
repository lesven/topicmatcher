<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Backoffice\BackofficeUser;
use App\Domain\Backoffice\UserRole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * Repository for BackofficeUser entities and password upgrading.
 * @extends ServiceEntityRepository<BackofficeUser>
 */
class BackofficeUserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    /**
     * BackofficeUserRepository constructor.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BackofficeUser::class);
    }

    public function save(BackofficeUser $user): void
    {
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function remove(BackofficeUser $user): void
    {
        $this->getEntityManager()->remove($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof BackofficeUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * @return BackofficeUser[]
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('u.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return BackofficeUser[]
     */
    public function findByRole(UserRole $role): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.role = :role')
            ->andWhere('u.isActive = :active')
            ->setParameter('role', $role)
            ->setParameter('active', true)
            ->orderBy('u.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find a BackofficeUser by email.
     *
     * @param string $email Email address to search for
     * @return BackofficeUser|null The user or null if not found
     */
    public function findByEmail(string $email): ?BackofficeUser
    {
        return $this->findOneBy(['email' => $email]);
    }
}