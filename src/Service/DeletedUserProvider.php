<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class DeletedUserProvider
{
    public function __construct(
        private EntityManagerInterface $em,
        private string $email,
    ) {
        $this->em = $em;
        $this->email = $email;
    }

    public function get(): User
    {
        $repo = $this->em->getRepository(User::class);
        $user = $repo->findOneBy(['email' => $this->email]);

        if ($user) {
            return $user;
        }


        $user = (new User())
            ->setEmail($this->email)
            ->setPassword('!')
            ->setName('Supprimé')
            ->setFirstname('Compte')
            ->setTitle('M')
            ->setStatus('Inactive')
            ->setRoles(['ROLE_USER']);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
}
