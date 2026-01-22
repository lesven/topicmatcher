<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Participation\Interest;
use App\Domain\Participation\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Interest>
 */
class InterestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Interest::class);
    }

    public function save(Interest $interest): void
    {
        $this->getEntityManager()->persist($interest);
        $this->getEntityManager()->flush();
    }

    public function remove(Interest $interest): void
    {
        $this->getEntityManager()->remove($interest);
        $this->getEntityManager()->flush();
    }

    public function findByPostAndEmail(Post $post, string $email): ?Interest
    {
        return $this->findOneBy(['post' => $post, 'email' => $email]);
    }

    /**
     * @return Interest[]
     */
    public function findByPost(Post $post): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.post = :post')
            ->setParameter('post', $post)
            ->orderBy('i.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countByPost(Post $post): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->andWhere('i.post = :post')
            ->setParameter('post', $post)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Check if duplicate interest exists (implements business rule)
     */
    public function isDuplicateInterest(Post $post, string $email): bool
    {
        return $this->findByPostAndEmail($post, $email) !== null;
    }

    public function getTotalCount(): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}