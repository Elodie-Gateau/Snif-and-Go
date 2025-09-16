<?php

namespace App\Repository;

use App\Entity\Trail;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Trail>
 */
class TrailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Trail::class);
    }



    public function search(array $criteria): array
    {
        $queryBuilder = $this->createQueryBuilder('trail')
            ->andWhere('trail.status = :statut')
            ->setParameter('statut', 'Active');

        if (!empty($criteria['search'])) {
            $term = '%' . strtolower(str_replace(['-', ' ', '_', '.'], '', trim($criteria['search']))) . '%';
            $queryBuilder
                ->andWhere('trail.nameSearch LIKE :term 
                OR trail.startCitySearch LIKE :term 
                OR trail.endCitySearch LIKE :term')
                ->setParameter('term', $term);
        }

        if (!empty($criteria['difficulty'])) {
            $queryBuilder
                ->andWhere('trail.difficulty = :difficulty')
                ->setParameter('difficulty', $criteria['difficulty']);
        }

        if (!empty($criteria['minDistance'])) {
            $queryBuilder
                ->andWhere('trail.distance >= :minDistance')
                ->setParameter('minDistance', (float) $criteria['minDistance']);
        }
        if (!empty($criteria['maxDistance'])) {
            $queryBuilder
                ->andWhere('trail.distance <= :maxDistance')
                ->setParameter('maxDistance', (float) $criteria['maxDistance']);
        }

        if (!empty($criteria['minDuration'])) {
            $queryBuilder
                ->andWhere('trail.duration >= :minDuration')
                ->setParameter('minDuration', (float) $criteria['minDuration']);
        }
        if (!empty($criteria['maxDuration'])) {
            $queryBuilder
                ->andWhere('trail.duration <= :maxDuration')
                ->setParameter('maxDuration', (float) $criteria['maxDuration']);
        }

        if (!empty($criteria['minScore'])) {
            $queryBuilder
                ->andWhere('trail.score >= :minScore')
                ->setParameter('minScore', (float) $criteria['minScore']);
        }
        if (!empty($criteria['maxScore'])) {
            $queryBuilder
                ->andWhere('trail.score <= :maxScore')
                ->setParameter('maxScore', (float) $criteria['maxScore']);
        }

        return $queryBuilder
            ->orderBy('trail.name', 'ASC')
            ->setMaxResults(8)
            ->getQuery()
            ->getResult();
    }
    //    /**
    //     * @return Trail[] Returns an array of Trail objects
    //     */
    public function findAllLimit(int $limit): array
    {
        return $this->createQueryBuilder('trail')
            ->andWhere('trail.status = :statut')
            ->setParameter('statut', 'Active')
            ->orderBy('trail.id', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    //    public function findOneBySomeField($value): ?Trail
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
