<?php

namespace App\Controller;

use App\Entity\Trail;
use App\Entity\Photo;
use App\Entity\User;
use App\Form\TrailType;
use Cloudinary\Cloudinary;
use App\Repository\TrailRepository;
use App\Repository\DogRepository;
use App\Repository\WalkRegistrationRepository;
use App\Repository\WalkRepository;
use App\Form\TrailSearchType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Service\GeocodingService;
use App\Service\DistanceService;
use App\Service\GpxService;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;


use Symfony\Component\Validator\Constraints\File as FileConstraint;
use Symfony\Component\Validator\Constraints\All;



#[Route('/trail')]
#[IsGranted('ROLE_USER')]
final class TrailController extends AbstractController

{
    #[Route(name: 'app_trail_index', methods: ['GET'])]
    public function index(
        WalkRepository $walkRepository,
        DogRepository $dogRepository,
        WalkRegistrationRepository $walkRegistrationRepository,
        Request $request,
        TrailRepository $trailRepository
    ): Response {

        $nextWalks = $walkRepository->findNext(4);

        $currentUser = $this->getUser();

        if ($currentUser) {
            $dogs = $dogRepository->findByUser($currentUser);
            if ($dogs && count($dogs) > 0) {
                foreach ($dogs as $dog) {
                    $wr = $walkRegistrationRepository->findNextWalkByDog($dog, $currentUser);
                    if ($wr) {
                        $dogNextWalks[$dog->getId()] = $wr;
                    } else {
                        $dogNextWalks = [];
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


        $searchForm = $this->createForm(TrailSearchType::class);
        $searchForm->handleRequest($request);

        $criteria = $searchForm->getData() ?? [];

        $foundTrails = [];
        if (
            !empty($criteria['search']) || !empty($criteria['difficulty'])
            || !empty($criteria['minDistance']) || !empty($criteria['maxDistance'])
            || !empty($criteria['minDuration']) || !empty($criteria['maxDuration'])
            || !empty($criteria['minScore']) || !empty($criteria['maxScore'])
        ) {
            $foundTrails = $trailRepository->search($criteria);
        } else {
            $foundTrails = $trailRepository->findAllLimit(8);
        }


        return $this->render('trail/index.html.twig', [
            'nextWalks'   => $nextWalks,
            'dogs'        => $dogs,
            'dogNextWalks' => $dogNextWalks,
            'form'        => $searchForm->createView(),
            'foundTrails' => $foundTrails,
        ]);
    }
    #[Route('/new', name: 'app_trail_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        GeocodingService $geocoding,
        DistanceService $distanceService,
        GpxService $gpx,
        SluggerInterface $slugger,
        Cloudinary $cloudinary
    ): Response {

        $user = $this->getUser();

        if (!$user instanceof User  || $user->getDogs()->isEmpty()) {
            $this->addFlash('notice', 'Vous devez ajouter au moins un chien avant de créer un itinéraire.');
            return $this->redirectToRoute('app_home');
        }
        $trail = new Trail();
        $trail->setUser($user);

        $form = $this->createForm(TrailType::class, $trail)->handleRequest($request);

        // Si le formumaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Si des images sont chargées
            if ($images = $form->get('photoFiles')->getData()) {
                foreach ($images as $image) {
                    // Pour chaque image récupère le nom du fichier, l'assainit et construit un nom unique de fichier
                    $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $image->guessExtension();
                    // Déplace l'image dans le dossier d'upload
                    $image->move($this->getParameter('images_directory'), $newFilename);
                    // Création d'une entité Photo
                    $photo = new Photo;
                    $photo->setName($newFilename);

                    try {
                        // Récupère le nom du fichier sans extention
                        $basenameNoExt = pathinfo($newFilename, PATHINFO_FILENAME);
                        // Construit le lien sans extension demandé par Cloudinary
                        $publicId = 'snifandgo/uploads/' . $basenameNoExt;
                        // Applique les modifications et enregistre dans le cloud Cloudinary l'image
                        $result = $cloudinary->uploadApi()->upload(
                            $this->getParameter('images_directory') . '/' . $newFilename,
                            [
                                'public_id'       => $publicId,
                                'overwrite'       => false,
                                'resource_type'   => 'image',
                                'format'        => 'webp',
                                'width'         => 300,
                                'height'        => 160,
                                'crop'          => 'fit',
                                'quality'       => 80
                            ]
                        );
                        // Enregistre dans l'entité Photo créé le nom de fichier nécessaire pour appeler l'image
                        $photo->setCdnLink($result['public_id'] ?? $publicId);
                    } catch (\Throwable $e) {

                        $this->addFlash('notice', "Votre photo est enregistrée");
                    }
                    $photo->setUser($this->getUser());
                    $photo->setTrail($trail);
                    $trail->addPhoto($photo);
                }
            }


            // On vérifie quel mode d'entrée des données est choisi : manuel ou fichier gpx
            $mode = $trail->getInputMode();

            // Si téléchargement d'un fichier GPX : on le stock sur server et on enregistre son lien
            if ($mode === 'gpx' && $file = $form->get('gpxFile')->getData()) {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();
                $file->move($this->getParameter('gpx_directory'), $newFilename);
                $trail->setGpxFile($newFilename);

                // On récupère le fichier récemment enregistré
                $filePath = $this->getParameter('gpx_directory') . '/' . $newFilename;

                // On lit le fichier GPX, on le parse (via méthode dans) GPXService
                if ($infos = $gpx->parse($filePath)) {

                    // On récupère la distance calculé en mètres et on la converti en kms ainsi que la durée
                    $meters = $infos['distance_m'];
                    $seconds = $infos['duration_s'];
                    $trail->setDistance(round($meters / 1000, 2));
                    $trail->setDuration(round($seconds / 60, 2));

                    $startLat = $infos['start']['lat'];
                    $startLon = $infos['start']['lon'];
                    $endLat = $infos['end']['lat'];
                    $endLon = $infos['end']['lon'];


                    // On enregistre les latitudes et longitudes des coordonnées début et fin
                    $trail->setStartLat($startLat);
                    $trail->setStartLon($startLon);
                    $trail->setEndLat($endLat);
                    $trail->setEndLon($endLon);

                    // On applique la fonction de reverse dans GeocodingService pour récupérer des adresses
                    // Si des résultats sont trouvés on les appliques à l'objet Trail créé
                    if ($rev = $geocoding->reverse($startLat, $startLon)) {
                        $trail->setStartAddress($rev['street'] ?: ($rev['label'] ?? ''));
                        $trail->setStartCity($rev['city'] ?? '');
                        $trail->setStartCode($rev['postcode'] ?? '');
                    }
                    if ($rev = $geocoding->reverse($endLat, $endLon)) {
                        $trail->setEndAddress($rev['street'] ?: ($rev['label'] ?? ''));
                        $trail->setEndCity($rev['city'] ?? '');
                        $trail->setEndCode($rev['postcode'] ?? '');
                    }
                } else {
                    $this->addFlash('error', "Fichier GPX invalide (aucun point exploitable).");
                }
            } else {
                // Si méthode de saisie manuelle des infos :

                // On défini une adresse de départ composée de l'adresse, du code postal et de la ville tout attaché
                $fromStr = trim(sprintf('%s %s %s', $trail->getStartAddress(), $trail->getStartCode(), $trail->getStartCity()));
                // On défini une adresse d'arrivée composée de l'adresse, du code postal et de la ville tout attaché
                $toStr = trim(sprintf('%s %s %s', $trail->getEndAddress(), $trail->getEndCode(), $trail->getEndCity()));

                // On récupère les coordonnées des adresses via la méthode dans le GeocodeService
                $from = $geocoding->geocode($fromStr);
                $to = $geocoding->geocode($toStr);

                // On enregistre les coordonnées dans le Trail nouvellement créé
                if ($from) {
                    $trail->setStartLat($from['lat']);
                    $trail->setStartLon($from['lon']);
                }
                if ($to) {
                    $trail->setEndLat($to['lat']);
                    $trail->setEndLon($to['lon']);
                }

                // Si des données sont trouvées...
                if (isset($from, $to)) {
                    // ... on définit une route avec la méthode osrm de DistanceService
                    $route = $distanceService->osrmFootRoute($from['lat'], $from['lon'], $to['lat'], $to['lon']);


                    if ($route) {
                        // On enregistre la distance convertie en km, et on fait une estimation de la durée
                        $km = $route['distance_m'] / 1000;
                        $trail->setDistance(round($km, 2));
                        $trail->setDuration($distanceService->estimateMinutesFromKm($km));
                    } else {
                        // sinon on tente la méthode Haversine + estimation durée
                        $meters = $distanceService->haversine($from['lat'], $from['lon'], $to['lat'], $to['lon']);
                        $km = $meters / 1000;
                        $trail->setDistance(round($km, 2));
                        $trail->setDuration($distanceService->estimateMinutesFromKm($km));
                    }
                }
            }
            $em->persist($trail);
            $em->flush();
            $this->addFlash('success', 'Itinéraire créé avec succès');
            return $this->redirectToRoute('app_trail_index');
        }

        return $this->render('trail/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    #[Route('/{id<\d+>}', name: 'app_trail_show', methods: ['GET', 'POST'])]
    public function show(
        Request $request,
        Trail $trail,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        WalkRepository $walkRepository,
        Cloudinary $cloudinary
    ): Response {
        $nextWalks = $walkRepository->findNextByTrail(10, $trail);


        $form = $this->createFormBuilder()
            ->add('photoFiles', FileType::class, [
                'label' => 'Téléchargez vos photos',
                'label_attr' => ['class' => 'trail__desc-title'],
                'mapped' => false,
                'multiple' => true,
                'required' => false,
                'attr' => ['accept' => 'image/*'],
                'constraints' => [
                    new All([
                        new FileConstraint([
                            'maxSize' => '5M',
                            'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
                            'mimeTypesMessage' => 'Formats acceptés : jpg, png, webp, gif',
                        ])
                    ])
                ],
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $images = $form->get('photoFiles')->getData();

            foreach ($images as $image) {
                $original = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $safe = $slugger->slug($original);
                $filename = $safe . '-' . uniqid() . '.' . $image->guessExtension();

                $image->move($this->getParameter('images_directory'), $filename);

                $photo = new Photo();
                $photo->setName($filename);
                try {
                    $basenameNoExt = pathinfo($filename, PATHINFO_FILENAME);
                    $publicId =  'snifandgo/uploads/' . $basenameNoExt;
                    $result = $cloudinary->uploadApi()->upload(
                        $this->getParameter('images_directory') . '/' . $filename,
                        [
                            'public_id'       => $publicId,
                            'overwrite'       => false,
                            'resource_type'   => 'image',
                            'format'        => 'webp',
                            'width'         => 300,
                            'height'        => 160,
                            'crop'          => 'fit',
                            'quality'       => 80
                        ]
                    );

                    $photo->setCdnLink($result['public_id'] ?? $publicId);
                } catch (\Throwable $e) {

                    $this->addFlash('notice', "Votre photo est enregistrée");
                }
                $photo->setUser($this->getUser());
                $photo->setTrail($trail);

                $trail->addPhoto($photo);
                $em->persist($photo);
            }

            $em->flush();

            $this->addFlash('success', 'Photos ajoutées avec succès');
            return $this->redirectToRoute('app_trail_show', ['id' => $trail->getId()]);
        }

        return $this->render('trail/show.html.twig', [
            'trail' => $trail,
            'form'  => $form->createView(),
            'nextWalks' => $nextWalks
        ]);
    }

    #[Route('/{id}/edit', name: 'app_trail_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Trail $trail, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TrailType::class, $trail);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('notice', 'Vos modifications sont enregistrées !');
            return $this->redirectToRoute('app_trail_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('trail/edit.html.twig', [
            'trail' => $trail,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/inactive', name: 'app_trail_inactive', methods: ['POST'])]
    public function inactive(Request $request, Trail $trail, EntityManagerInterface $entityManager): Response
    {
        $submittedToken = $request->request->get('_token');

        if ($this->isCsrfTokenValid('inactive' . $trail->getId(), $submittedToken)) {
            $trail->setStatus("Inactive");
            $entityManager->flush();

            $this->addFlash('warning', 'Votre itinéraire a été supprimé');
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide, action annulée.');
        }

        return $this->redirectToRoute('app_home');
    }
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}/delete', name: 'app_trail_delete', methods: ['POST'])]
    public function delete(Request $request, Trail $trail, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $trail->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($trail);
            $entityManager->flush();

            $this->addFlash('warning', "L'itinéraire a été supprimé");
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide, action annulée.');
        }

        return $this->redirectToRoute('admin_user_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/live-search', name: 'app_trail_live_search', methods: ['GET'])]
    public function liveSearch(Request $request, TrailRepository $trailRepository): JsonResponse
    {
        $query = $request->query->get('q', '');
        if (mb_strlen($query) < 3) {
            return $this->json([]);
        }
        $criteria['search'] = $query;
        $out = $trailRepository->search($criteria);
        $results = [];
        foreach ($out as $trail) {
            $results[] = [
                'id'           => $trail->getId(),
                'name'         => $trail->getName(),
                'startCity'    => $trail->getStartCity(),
                'distance'     => $trail->getDistance(),
                'duration'     => $trail->getDuration(),
                'difficulty' => $trail->getDifficulty(),
                'photo'        => (count($trail->getPhotos()) > 0) ? $trail->getPhotos()[0]->getName() : null,
                'url'          => $this->generateUrl('app_trail_show', ['id' => $trail->getId()]),
            ];
        }
        return $this->json($results);
    }
}
