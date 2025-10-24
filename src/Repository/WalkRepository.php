<?php

namespace App\Repository;

use App\Entity\Walk;
use App\Entity\Trail;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Walk>
 */
class WalkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Walk::class);
    }

    //    /**
    //     * @return Walk[] Returns an array of Walk objects
    //     */
    public function findNext(int $limit): array
    {
        return $this->createQueryBuilder('walk')
            ->andWhere('walk.date >= :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->andWhere('walk.status = :statut')
            ->setParameter('statut', 'Active')
            ->orderBy('walk.date', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
    public function findAllNext(): array
    {
        return $this->createQueryBuilder('walk')
            ->andWhere('walk.date >= :now')
            ->andWhere('walk.status = :statut')
            ->setParameter('statut', 'Active')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('walk.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findNextByTrail(int $limit = 10, Trail $trail): array
    {
        return $this->createQueryBuilder('walk')
            ->andWhere('walk.date >= :today')
            ->andWhere('walk.trail = :trail')
            ->andWhere('walk.status = :statut')
            ->setParameter('statut', 'Active')
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->setParameter('trail', $trail)
            ->orderBy('walk.date', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findOneById(int $id): ?Walk
    {
        return $this->createQueryBuilder('walk')
            ->andWhere('walk.id = :id')
            ->andWhere('walk.status = :status')
            ->setParameter('id', $id)
            ->setParameter('status', 'Active')
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findAllByStatus($status): array
    {
        return $this->createQueryBuilder('walk')
            ->andWhere('walk.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getResult()
        ;
    }
}
