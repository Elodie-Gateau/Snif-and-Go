<?php

namespace App\Controller\Admin;

use App\Service\DeletedUserProvider;
use App\Entity\User;
use App\Form\UserType;
use App\Repository\DogRepository;
use App\Repository\TrailRepository;
use App\Repository\UserRepository;
use App\Repository\WalkRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/user', name: 'admin_user_')]

final class AdminUserController extends AbstractController
{
    #[Route(name: 'index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(UserRepository $userRepository, DogRepository $dogRepository, TrailRepository $trailRepository, WalkRepository $walkRepository): Response
    {
        $allUsers = $userRepository->findAll();
        $activeUsers = $userRepository->findAllByStatus('Active');

        $allDogs = $dogRepository->findAll();
        $activeDogs = $dogRepository->findAllByStatus('Active');

        $allTrails = $trailRepository->findAll();
        $activeTrails = $trailRepository->findAllByStatus('Active');

        $allWalks = $walkRepository->findAll();
        $activeWalks = $walkRepository->findAllByStatus('Active');


        return $this->render('admin/user/index.html.twig', [
            'users' => $allUsers,
            'activeUsers' => $activeUsers,
            'dogs' => $allDogs,
            'activeDogs' => $activeDogs,
            'trails' => $allTrails,
            'activeTrails' => $activeTrails,
            'walks' => $allWalks,
            'activeWalks' => $activeWalks
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('admin_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('admin/user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->getUser() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous ne pouvez éditer que votre profil.');
        }
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            if ($this->isGranted('ROLE_ADMIN')) {
                $this->addFlash('success', 'Informations mises à jour.');
                return $this->redirectToRoute('admin_user_index', [], Response::HTTP_SEE_OTHER);
            } else {
                $this->addFlash('success', 'Vos informations sont mises à jour.');
                return $this->redirectToRoute('app_profile', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('admin/user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(
        Request $request,
        UserRepository $userRepository,
        User $user,
        EntityManagerInterface $entityManager,
        DeletedUserProvider $deletedUserProvider
    ): Response {
        // Vérification du Csrf Token
        if (!$this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('CSRF token invalid.');
        }
        // Récupération du compte spécial de réaffectation des données
        $deletedUser = $deletedUserProvider->get();

        // Ce compte spécial ne peut pas être supprimé
        if ($user->getId() === $deletedUser->getId()) {
            $this->addFlash('warning', 'Ce compte spécial ne peut pas être supprimé.');
            return $this->redirectToRoute('admin_user_index');
        }

        // Récupération des trails et photos du user à supprimer
        $trails = $user->getTrails();
        $photos = $user->getPhotos();

        // Affectation des données au compte Deleted User
        if ($trails) {
            foreach ($trails as $trail) {
                $trail->setUser($deletedUser);
            }
        }
        if ($photos) {
            foreach ($photos as $photo) {
                $photo->setUser($deletedUser);
            }
        }

        // Suppression des autres données (dogs, walks et walks registration)
        $userRepository->delete($user);

        // Suppresion du user
        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('success', 'Utilisateur supprimé, contenus ré-attribués.');
        return $this->redirectToRoute('admin_user_index');
    }

    #[Route('/{id}/inactive', name: 'inactive', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function inactive(Request $request, User $user, EntityManagerInterface $em,  UserRepository $userRepository): Response
    {
        $submittedToken = $request->request->get('_token');

        if (!$this->isCsrfTokenValid('inactive' . $user->getId(), $submittedToken)) {
            throw $this->createAccessDeniedException('CSRF token invalid.');
        }
        $userRepository->desactivate($user);
        $user->setStatus('Inactive');
        $em->flush();
        $this->addFlash('success', 'Votre compte est désactivé.');

        if ($this->getUser() && $this->getUser()->getUserIdentifier() === $user->getUserIdentifier()) {
            return $this->redirectToRoute('app_logout');
        }
        return $this->redirectToRoute('admin_user_index');
    }

    #[Route('/{id}/ban', name: 'ban', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function ban(Request $request, User $user, EntityManagerInterface $em, UserRepository $userRepository): Response
    {
        $submittedToken = $request->request->get('_token');

        if (!$this->isCsrfTokenValid('ban' . $user->getId(), $submittedToken)) {
            throw $this->createAccessDeniedException('CSRF token invalid.');
        }
        $userRepository->desactivate($user);
        $user->setStatus('Banned');
        $em->flush();
        $this->addFlash('success', sprintf('Utilisateur banni.', $user->getEmail()));

        if ($this->getUser() && $this->getUser()->getUserIdentifier() === $user->getUserIdentifier()) {
            return $this->redirectToRoute('app_logout');
        }
        return $this->redirectToRoute('admin_user_index');
    }

    #[Route('/{id}/unban', name: 'unban', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function unban(Request $request, User $user, EntityManagerInterface $em, UserRepository $userRepository): Response
    {
        $submittedToken = $request->request->get('_token');

        if (!$this->isCsrfTokenValid('unban' . $user->getId(), $submittedToken)) {
            throw $this->createAccessDeniedException('CSRF token invalid.');
        }
        $userRepository->reactivate($user);
        $user->setStatus('Active');
        $em->flush();
        $this->addFlash('success', sprintf('Utilisateur %s réactivé.', $user->getEmail()));

        return $this->redirectToRoute('admin_user_index');
    }
}
