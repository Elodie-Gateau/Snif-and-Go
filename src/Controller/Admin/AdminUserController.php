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
        $activeUsers = [];
        foreach ($allUsers as $user) {
            if ($user->getStatus() === "Active") {
                $activeUsers[] = $user;
            }
        }
        $allDogs = $dogRepository->findAll();
        $activeDogs = [];
        foreach ($allDogs as $dog) {
            if ($dog->getStatus() === "Active") {
                $activeDogs[] = $dog;
            }
        }
        $allTrails = $trailRepository->findAll();
        $activeTrails = [];
        foreach ($allTrails as $trail) {
            if ($trail->getStatus() === "Active") {
                $activeTrails[] = $trail;
            }
        }
        $allWalks = $walkRepository->findAll();
        $activeWalks = [];
        foreach ($allWalks as $walk) {
            if ($walk->getStatus() === "Active") {
                $activeWalks[] = $walk;
            }
        }
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
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('admin_user_index', [], Response::HTTP_SEE_OTHER);
            } else {
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
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager, DeletedUserProvider $deletedUserProvider): Response
    {
        if (!$this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('CSRF token invalid.');
        }
        $deletedUser = $deletedUserProvider->get();

        if ($user->getId() === $deletedUser->getId()) {
            $this->addFlash('warning', 'Ce compte spécial ne peut pas être supprimé.');
            return $this->redirectToRoute('admin_user_index');
        }

        $dogs = $user->getDogs();
        $trails = $user->getTrails();
        $walks = $user->getWalks();
        $photos = $user->getPhotos();

        if ($dogs) {
            foreach ($dogs as $dog) {
                $dog->setStatus('Inactive');
                $dog->setUser($deletedUser);
            }
        }
        if ($trails) {
            foreach ($trails as $trail) {
                $trail->setUser($deletedUser);
            }
        }
        if ($walks) {
            foreach ($walks as $walk) {
                $walk->setStatus('Inactive');
                $walk->setUser($deletedUser);
            }
        }
        if ($photos) {
            foreach ($photos as $photo) {
                $photo->setUser($deletedUser);
            }
        }
        $entityManager->flush();
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
