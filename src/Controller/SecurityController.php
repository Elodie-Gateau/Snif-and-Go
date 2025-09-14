<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\UserRepository;
use App\Entity\User;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
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

    #[IsGranted('ROLE_USER')]
    #[Route('/account/reactivate', name: 'app_account_reactivate', methods: ['GET', 'POST'])]
    public function reactivate(Request $request, EntityManagerInterface $em, UserRepository $userRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($user->getStatus() !== 'Inactive') {
            return $this->redirectToRoute('app_home');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('reactivate', $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('CSRF invalide.');
            }
            $userRepository->reactivate($user);
            $user->setStatus('Active');

            $em->flush();
            $this->addFlash('success', 'Votre compte et vos contenus ont été réactivés.');
            return $this->redirectToRoute('app_home');
        }
        return $this->render('security/reactivate.html.twig');
    }
}
