<?php

namespace App\Controller;

use App\Entity\Dog;
use App\Form\DogType;
use App\Repository\DogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dog')]
#[IsGranted('ROLE_USER')]
final class DogController extends AbstractController
{
    #[Route(name: 'app_dog_index', methods: ['GET'])]
    public function index(DogRepository $dogRepository): Response
    {
        return $this->render('dog/index.html.twig', [
            'dogs' => $dogRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_dog_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $dog = new Dog();
        $dog->setUser($this->getUser());
        $form = $this->createForm(DogType::class, $dog);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('photo')->getData();
            if ($imageFile) {

                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);

                $safeFilename = $slugger->slug($originalFilename);

                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                $imageFile->move(
                    $this->getParameter('images_directory'),
                    $newFilename
                );
                $dog->setPhoto($newFilename);
            }
            $entityManager->persist($dog);
            $entityManager->flush();

            return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dog/new.html.twig', [
            'dog' => $dog,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_dog_show', methods: ['GET'])]
    public function show(Dog $dog): Response
    {
        return $this->render('dog/show.html.twig', [
            'dog' => $dog,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_dog_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Dog $dog, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DogType::class, $dog);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_dog_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dog/edit.html.twig', [
            'dog' => $dog,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/inactive', name: 'app_dog_inactive', methods: ['POST'])]
    public function inactive(Request $request, Dog $dog, EntityManagerInterface $entityManager): Response
    {
        $dog->setStatus("Inactive");
        $entityManager->flush();


        return $this->redirectToRoute('app_profile');
    }

    #[Route('/{id}', name: 'app_dog_delete', methods: ['POST'])]
    public function delete(Request $request, Dog $dog, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $dog->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($dog);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_dog_index', [], Response::HTTP_SEE_OTHER);
    }
}
