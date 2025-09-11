<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        $status = $user->getStatus();

        if ($status === 'Banned') {
            throw new CustomUserMessageAccountStatusException('Votre compte a été banni.');
        }

        // if ($status === 'Inactive') {
        //     throw new CustomUserMessageAccountStatusException('Your account is inactive.');
        // }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // Rien de spécial ici pour l’instant.
    }
}
