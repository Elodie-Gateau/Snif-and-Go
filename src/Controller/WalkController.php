<?php

namespace App\Controller;

use App\Entity\Walk;
use App\Form\WalkType;
use App\Repository\TrailRepository;
use App\Repository\WalkRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/walk')]
#[IsGranted('ROLE_USER')]
final class WalkController extends AbstractController
{
    #[Route(name: 'app_walk_index', methods: ['GET'])]
    public function index(WalkRepository $walkRepository): Response
    {
        return $this->render('walk/index.html.twig', [
            'nextWalks' => $walkRepository->findAllNext(),
        ]);
    }

    #[Route('/new', name: 'app_walk_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, TrailRepository $trailRepository): Response
    {
        $user = $this->getUser();
        $trail = null;
        $walk = new Walk();
        $trailId = $request->query->getInt('trailId', 0);
        if ($trailId > 0) {
            $trail = $trailRepository->find($trailId);
            if ($trail) {
                $walk->setTrail($trail);
            }
        }

        $walk->setUser($user);

        $form = $this->createForm(WalkType::class, $walk);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($walk);
            $entityManager->flush();
            $this->addFlash('notice', 'Votre balade est planifiée');
            return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('walk/new.html.twig', [
            'walk' => $walk,
            'form' => $form,
            'trail' => $trail
        ]);
    }

    #[Route('/{id}', name: 'app_walk_show', methods: ['GET'])]
    public function show(Walk $walk): Response
    {
        return $this->render('walk/show.html.twig', [
            'walk' => $walk,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_walk_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Walk $walk, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(WalkType::class, $walk);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('notice', 'Vos modifications sont enregistrées !');
            return $this->redirectToRoute('app_walk_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('walk/edit.html.twig', [
            'walk' => $walk,
            'form' => $form,

        ]);
    }

    #[Route('/{id}/inactive', name: 'app_walk_inactive', methods: ['POST'])]
    public function inactive(Request $request, Walk $walk, EntityManagerInterface $entityManager): Response
    {
        $submittedToken = $request->request->get('_token');

        if ($this->isCsrfTokenValid('inactive' . $walk->getId(), $submittedToken)) {
            $walk->setStatus("Inactive");
            $entityManager->flush();

            $this->addFlash('warning', 'Votre balade a été annulée');
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide, action annulée.');
        }
        return $this->redirectToRoute('app_home');
    }


    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}', name: 'app_walk_delete', methods: ['POST'])]
    public function delete(Request $request, Walk $walk, EntityManagerInterface $entityManager): Response
    {
        $submittedToken = $request->request->get('_token');

        if ($this->isCsrfTokenValid('delete' . $walk->getId(), $submittedToken)) {
            $entityManager->remove($walk);
            $entityManager->flush();
            $this->addFlash('warning', 'La balade a été supprimé');
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide, suppression annulée.');
        }

        return $this->redirectToRoute('admin_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
