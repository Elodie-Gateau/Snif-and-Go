<?php

namespace App\Repository;

use App\Entity\Dog;
use App\Entity\User;
use App\Entity\Walk;

use App\Entity\WalkRegistration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WalkRegistration>
 */
class WalkRegistrationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WalkRegistration::class);
    }


    public function findNextWalkByDog(Dog $dog, User $user): ?WalkRegistration
    {
        return $this->createQueryBuilder('wr')
            ->join('wr.walk', 'w')
            ->join('wr.dog', 'd')
            ->join('w.trail', 't')
            ->andWhere('wr.dog = :dog')
            ->andWhere('d.user = :user')
            ->andWhere('w.date >= :now')
            ->andWhere('w.status = :wstatus')
            ->andWhere('wr.status = :wrstatus')
            ->andWhere('t.status = :tstatus')
            ->setParameter('dog', $dog)
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('wstatus', 'Active')
            ->setParameter('wrstatus', 'Active')
            ->setParameter('tstatus', 'Active')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
    public function findValidRegisters(Walk $walk): ?array
    {
        return $this->createQueryBuilder('wr')
            ->join('wr.walk', 'w')
            ->join('wr.dog', 'd')
            ->andWhere('wr.walk = :walk')
            ->andWhere('wr.status = :wrstatus')
            ->andWhere('d.status = :dstatus')
            ->setParameter('walk', $walk)
            ->setParameter('wrstatus', 'Active')
            ->setParameter('dstatus', 'Active')
            ->orderBy('wr.date_registration', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
//    /**
//     * @return WalkRegistration[] Returns an array of WalkRegistration objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('w')
//            ->andWhere('w.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('w.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?WalkRegistration
//    {
//        return $this->createQueryBuilder('w')
//            ->andWhere('w.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
