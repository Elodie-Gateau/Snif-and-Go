<?php

namespace App\Controller;

use App\Entity\User;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }
        $allTrails = $user->getTrails();
        $trails = [];
        foreach ($allTrails as $oneTrail) {
            if ($oneTrail->getStatus() === "Active") {
                $trails[] = $oneTrail;
            }
        }
        $allDogs = $user->getDogs();
        $dogs = [];
        foreach ($allDogs as $oneDog) {
            if ($oneDog->getStatus() === "Active") {
                $dogs[] = $oneDog;
            }
        }


        $allWalks = $user->getWalks();
        $walks = [];
        foreach ($allWalks as $oneWalk) {
            if ($oneWalk->getStatus() === "Active") {
                $walks[] = $oneWalk;
            }
        }

        $walkRegistrations = [];
        foreach ($dogs as $dog) {
            $wrs = $dog->getWalkRegistration();
            foreach ($wrs as $wr) {
                if ($wr->getStatus() === "Active") {
                    $walkRegistrations[] = $wr;
                }
            }
        }
        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'trails' => $trails,
            'dogs' => $dogs,
            'walks' => $walks,
            'walkRegistrations' => $walkRegistrations
        ]);
    }

    #[Route('/profile/download-data', name: 'profile_download_data')]
    #[IsGranted('ROLE_USER')]
    public function downloadData(): Response
    {
        $user = $this->getUser();
        /** @var \App\Entity\User $user */
        $data = [
            'email' => $user->getEmail(),
            'title' => $user->getTitle(),
            'firstname' => $user->getFirstname(),
            'name' => $user->getName(),
            'dogs' => array_map(fn($dog) => [
                'name' => $dog->getName(),
                'status' => $dog->getStatus(),
                'birth_date' => $dog->getBirthDate(),
                'breed' => $dog->getDogBreed(),
                'sex' => $dog->getSex()
            ], $user->getDogs()->toArray()),
            'trails' => array_map(fn($trail) => [
                'name' => $trail->getName(),
                'status' => $trail->getStatus(),
                'distance' => $trail->getDistance(),
                'duration' => $trail->getDuration(),
                'difficulty' => $trail->getDifficulty(),
                'waterpoint' => $trail->getWaterPoint(),
                'startAddress' => $trail->getStartAddress(),
                'startCode' => $trail->getStartCode(),
                'startCity' => $trail->getStartCity(),
                'endAddress' => $trail->getEndAddress(),
                'endCode' => $trail->getEndCode(),
                'endCity' => $trail->getEndCity(),
            ], $user->getTrails()->toArray()),
            'walks' => array_map(fn($walk) => [
                'date' => $walk->getDate(),
                'maxDogs' => $walk->getMaxDogs(),
                'status' => $walk->getStatus(),
            ], $user->getWalks()->toArray()),
            'photos' => array_map(fn($photo) => [
                'date' => $photo->getDate(),
                'name' => $photo->getName(),
            ], $user->getPhotos()->toArray()),
        ];

        // Conversion en JSON
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return new Response($json, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="mes_donnees.json"',
        ]);
    }
}
