<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\UserRepository;
use App\Entity\User;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, Request $request, EntityManagerInterface $em, UserRepository $userRepository): Response
    {
        // Récupération de l'user qui se connecte
        /** @var User|null $user */
        $user = $this->getUser();

        // Si l'utilisateur n'existe pas, la page login s'affiche
        if (!$user instanceof User) {
            $error = $authenticationUtils->getLastAuthenticationError();
            $lastUsername = $authenticationUtils->getLastUsername();

            return $this->render('security/login.html.twig', [
                'last_username' => $lastUsername,
                'error' => $error,
            ]);
        }

        // Utilisateur connecté : si Inactive -> on réactive, sinon redirection à la page d'accueil
        if ($user->getStatus() === 'Inactive') {
            $userRepository->reactivate($user);
            $user->setStatus('Active');
            $em->flush();

            $this->addFlash('success', 'Votre compte et vos contenus ont été réactivés.');
        }

        return $this->redirectToRoute('app_home');
    }


    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/post-login', name: 'app_post_login')]
    public function postLogin(EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($user->getStatus() === 'Inactive') {

            return $this->render('security/reactivate.html.twig');
        }


        return $this->redirectToRoute('app_home');
    }
}
