<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }


    public function desactivate(User $user): void
    {
        $conn = $this->getEntityManager()->getConnection();
        $conn->beginTransaction();
        try {
            $conn->executeStatement(
                "UPDATE dog  SET status = 'Inactive' WHERE status = 'Active' AND user_id = :user_id;",
                ["user_id" => $user->getId()]
            );
            $conn->executeStatement(
                "UPDATE walk SET status = 'Inactive' WHERE status = 'Active' AND user_id = :user_id;",
                ["user_id" => $user->getId()]
            );

            $conn->executeStatement(
                "UPDATE walk_registration wr JOIN dog d ON d.id = wr.dog_id SET wr.status = 'Cancelled' 
                WHERE d.user_id = :user_id AND wr.status = 'Active';",
                ['user_id' => $user->getId()]
            );

            $conn->executeStatement(
                "UPDATE walk_registration wr JOIN walk w ON w.id = wr.walk_id SET wr.status = 'Cancelled' 
                WHERE w.user_id = :user_id AND wr.status = 'Active';",
                ['user_id' => $user->getId()]
            );
            $conn->commit();
        } catch (\Throwable $e) {
            $this->logger->error('Failed to deactivate user', [
                'user_id' => $user->getId(),
                'error'   => $e->getMessage(),
                'code'    => $e->getCode(),
            ]);

            $conn->rollBack();
            throw $e;
        }
    }
    public function reactivate(User $user): void
    {
        $conn = $this->getEntityManager()->getConnection();
        $conn->beginTransaction();

        try {
            $conn->executeStatement(
                "UPDATE dog  SET status = 'Active' WHERE status = 'Inactive' AND user_id = :user_id;",
                ["user_id" => $user->getId()]
            );
            $conn->executeStatement(
                "UPDATE walk SET status = 'Active' WHERE status = 'Inactive' AND user_id = :user_id;",
                ["user_id" => $user->getId()]
            );
            $conn->executeStatement(
                "UPDATE walk_registration wr
                    JOIN dog d ON d.id = wr.dog_id
                    SET wr.status = 'Cancelled'
                    WHERE d.user_id = :user_id
                    AND wr.status = 'Cancelled';",
                ['user_id' => $user->getId()]
            );

            $conn->executeStatement(
                "UPDATE walk_registration wr
                    JOIN walk w ON w.id = wr.walk_id
                    SET wr.status = 'Cancelled'
                    WHERE w.user_id = :user_id
                    AND wr.status = 'Cancelled';",
                ['user_id' => $user->getId()]
            );
            $conn->commit();
        } catch (\Throwable $e) {

            $this->logger->error('Failed to deactivate user', [
                'user_id' => $user->getId(),
                'error'   => $e->getMessage(),
                'code'    => $e->getCode(),
            ]);

            $conn->rollBack();
            throw $e;
        }
    }
    public function delete($user): void
    {
        $conn = $this->getEntityManager()->getConnection();
        $conn->beginTransaction();

        try {
            $conn->executeStatement(
                "DELETE d FROM dog d
                WHERE d.user_id = :user_id;",
                ["user_id" => $user->getId()]
            );
            $conn->executeStatement(
                "DELETE w FROM walk w
                WHERE w.user_id = :user_id;",
                ["user_id" => $user->getId()]
            );
            $conn->commit();
        } catch (\Throwable $e) {
            $this->logger->error('Failed to deactivate user', [
                'user_id' => $user->getId(),
                'error'   => $e->getMessage(),
                'code'    => $e->getCode(),
            ]);

            $conn->rollBack();
            throw $e;
        }
    }

    public function findAllByStatus($status): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getResult()
        ;
    }
}
