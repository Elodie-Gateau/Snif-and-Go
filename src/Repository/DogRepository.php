<?php

namespace App\Repository;

use App\Entity\Dog;
use App\Entity\User;
use App\Entity\Walk;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Dog>
 */
class DogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dog::class);
    }


    public function findAvailableForWalk(User $user, Walk $walk): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT d.id
                FROM dog d
                WHERE d.user_id = :user_id
                AND d.status  = 'Active'
                AND d.identity_number IS NOT null
                AND NOT EXISTS (
                    SELECT 1
                    FROM walk_registration wr
                    WHERE wr.dog_id = d.id
                    AND wr.walk_id = :walk_id
                    AND wr.status  = 'Active')";

        $resultSet = $conn->executeQuery($sql, [
            'user_id' => $user->getId(),
            'walk_id' => $walk->getId(),
        ]);
        $dogsId = $resultSet->fetchAllAssociative();

        $dogIds = array_map('intval', array_column($dogsId, 'id'));

        if (!$dogIds) {
            return [];
        } else {

            return $this->createQueryBuilder('d')
                ->andWhere('d.id IN (:ids)')
                ->setParameter('ids', $dogIds)
                ->orderBy('d.name', 'ASC')
                ->getQuery()
                ->getResult();
        }
    }

    public function findByUser($user): array
    {
        return $this->createQueryBuilder('dog')
            ->andWhere('dog.user = :user')
            ->andWhere('dog.status = :statut')
            ->setParameter('statut', 'Active')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult()
        ;
    }

    //    /**
    //     * @return Dog[] Returns an array of Dog objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('d.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }
}
