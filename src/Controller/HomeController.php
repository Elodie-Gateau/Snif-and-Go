<?php

namespace App\Controller;

use App\Repository\DogRepository;
use App\Repository\WalkRepository;
use App\Repository\WalkRegistrationRepository;
use App\Form\TrailSearchType;
use App\Repository\TrailRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;


final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET', 'POST'])]
    public function index(
        WalkRepository $walkRepository,
        DogRepository $dogRepository,
        WalkRegistrationRepository $walkRegistrationRepository,
        Request $request,
        TrailRepository $trailRepository
    ): Response {

        $limit = 3;
        $nextWalks = $walkRepository->findNext($limit + 1);
        $spotsByWalk = [];
        if ($nextWalks && !empty($nextWalks)) {
            foreach ($nextWalks as $walk) {
                $validRegisters = $walkRegistrationRepository->findValidRegisters($walk);
                if ($validRegisters) {
                    $countValidRegisters = count($validRegisters);
                } else {
                    $countValidRegisters = 0;
                }
                $spotsByWalk[$walk->getId()] = ($walk->getMaxDogs() - $countValidRegisters);
            }
        }
        $hasMoreWalks = count($nextWalks) > $limit;
        $currentUser = $this->getUser();

        if ($currentUser) {
            $dogs = $dogRepository->findByUser($currentUser);
            if ($dogs && count($dogs) > 0) {
                foreach ($dogs as $dog) {
                    $wr = $walkRegistrationRepository->findNextWalkByDog($dog, $currentUser);
                    if ($wr) {
                        $dogNextWalks[$dog->getId()] = $wr;
                    } else {
                        $dogNextWalks[$dog->getId()] = null;
                    }
                }
            } else {
                $dogs = [];
                $dogNextWalks = [];
            }
        } else {
            $dogs = [];
            $dogNextWalks = [];
        }
        $hasMoreDogs = count($dogs) > $limit;

        $searchForm = $this->createForm(TrailSearchType::class);
        $searchForm->handleRequest($request);


        $hasMoreTrails = false;
        $criteria = $searchForm->getData() ?? [];
        if ($searchForm->isSubmitted() && $searchForm->isValid()) {

            $foundTrails = [];
            $criteria = $searchForm->getData() ?? [];
            if (
                !empty($criteria['search']) || !empty($criteria['difficulty'])
                || !empty($criteria['minDistance']) || !empty($criteria['maxDistance'])
                || !empty($criteria['minDuration']) || !empty($criteria['maxDuration'])
                || !empty($criteria['minScore']) || !empty($criteria['maxScore'])
            ) {

                $foundTrails = $trailRepository->search($criteria);
                $limitTrail = 5;
                $hasMoreTrails = count($foundTrails) > $limitTrail;
            }
        } else {
            $limitTrail = 5;
            $foundTrails = $trailRepository->findAllLimit($limitTrail + 1);
            $hasMoreTrails = count($foundTrails) > $limitTrail;
        }


        return $this->render('home/index.html.twig', [
            'nextWalks'   => $nextWalks,
            'spotsByWalk' => $spotsByWalk,
            'hasMoreWalks' => $hasMoreWalks,
            'hasMoreDogs' => $hasMoreDogs,
            'dogs'        => $dogs,
            'dogNextWalks' => $dogNextWalks,
            'form'        => $searchForm->createView(),
            'foundTrails' => $foundTrails,
            'hasMoreTrails' => $hasMoreTrails
        ]);
    }
}
