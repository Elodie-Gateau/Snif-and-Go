<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Dog;
use App\Entity\Walk;
use App\Entity\Trail;
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
        $trails = $user->getTrails();
        $dogs = $user->getDogs();
        $now = new DateTime('now');
        $walkRegistrations = [];
        foreach ($dogs as $dog) {

            $wrs = $dog->getWalkRegistration();
            foreach ($wrs as $wr) {
                $walk = $wr->getWalk();
                if ($walk->getDate()->getTimestamp() < $now->getTimestamp()) {
                    $walkRegistrations[] = $wr;
                }
            }
        }
        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'trails' => $trails,
            'dogs' => $dogs,
            'walkRegistrations' => $walkRegistrations
        ]);
    }
}
