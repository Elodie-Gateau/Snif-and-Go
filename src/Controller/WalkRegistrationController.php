<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Walk;
use App\Entity\Dog;
use App\Entity\WalkRegistration;
use App\Form\WalkRegistrationType;
use App\Repository\DogRepository;
use App\Repository\WalkRegistrationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\WalkRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[Route('/walk/registration')]
#[IsGranted('ROLE_USER')]
final class WalkRegistrationController extends AbstractController
{
    #[Route(name: 'app_walk_registration_index', methods: ['GET'])]
    public function index(WalkRegistrationRepository $walkRegistrationRepository): Response
    {
        return $this->render('walk_registration/index.html.twig', [
            'walk_registrations' => $walkRegistrationRepository->findAll(),
        ]);
    }

    #[Route('/new/{walkId}', name: 'app_walk_registration_new', methods: ['GET', 'POST'])]
    public function new(int $walkId, WalkRegistrationRepository $walkRegistrationRepository, WalkRepository $walkRepository, DogRepository $dogRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        $walk = $walkRepository->find($walkId);
        $walkRegistration = new WalkRegistration();
        $walkRegistration->setWalk($walk);


        $availableDogs = $dogRepository->findAvailableForWalk($this->getUser(), $walk);


        $validRegisters = $walkRegistrationRepository->findValidRegisters($walk);


        $form = $this->createForm(
            WalkRegistrationType::class,
            $walkRegistration,
            ['dogs' => $availableDogs]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($walkRegistration);
            $entityManager->flush();
            $this->addFlash('success', 'Votre inscription est validée');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('walk_registration/new.html.twig', [
            'walk' => $walk,
            'validRegisters' => $validRegisters,
            'form' => $form,
            'dogs' => $availableDogs
        ]);
    }

    #[Route('/{id}', name: 'app_walk_registration_show', methods: ['GET'])]
    public function show(WalkRegistration $walkRegistration, WalkRegistrationRepository $walkRegistrationRepository, WalkRepository $walkRepository): Response
    {
        $walk = $walkRegistration->getWalk();
        $validRegisters = $walkRegistrationRepository->findValidRegisters($walk);
        return $this->render('walk_registration/show.html.twig', [
            'wr' => $walkRegistration,
            'walk' => $walk,
            'validRegisters' => $validRegisters
        ]);
    }

    #[Route('/{id}/edit', name: 'app_walk_registration_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, WalkRegistration $walkRegistration, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(WalkRegistrationType::class, $walkRegistration);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('notice', 'Vos modifications sont enregistrées');
            return $this->redirectToRoute('app_walk_registration_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('walk_registration/edit.html.twig', [
            'walk_registration' => $walkRegistration,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/cancelled', name: 'app_walk_registration_cancelled', methods: ['POST'])]
    public function inactive(Request $request, WalkRegistration $walkRegistration, EntityManagerInterface $entityManager): Response
    {
        $submittedToken = $request->request->get('_token');

        if ($this->isCsrfTokenValid('cancel' . $walkRegistration->getId(), $submittedToken)) {
            $walkRegistration->setStatus("cancelled");
            $entityManager->flush();
            $this->addFlash('warning', 'Vous êtes désinscrit de cette balade');
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide, action annulée.');
        }
        return $this->redirectToRoute('app_home');
    }
    #[Route('/{id}', name: 'app_walk_registration_delete', methods: ['POST'])]
    public function delete(Request $request, WalkRegistration $walkRegistration, EntityManagerInterface $entityManager): Response
    {
        $submittedToken = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete' . $walkRegistration->getId(), $submittedToken)) {
            $entityManager->remove($walkRegistration);
            $entityManager->flush();
            $this->addFlash('warning', "L'inscription est supprimée");
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide, suppression annulée.');
        }

        return $this->redirectToRoute('app_walk_registration_index', [], Response::HTTP_SEE_OTHER);
    }
}
