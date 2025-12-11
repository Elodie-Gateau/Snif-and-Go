<?php

namespace App\Controller;

use App\Entity\Trail;
use App\Entity\Photo;
use App\Entity\User;
use App\Form\TrailType;
use App\Form\PhotoType;
use Cloudinary\Cloudinary;
use App\Repository\TrailRepository;
use App\Repository\DogRepository;
use App\Repository\WalkRegistrationRepository;
use App\Repository\WalkRepository;
use App\Form\TrailSearchType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Service\GeocodingService;
use App\Service\DistanceService;
use App\Service\GpxService;
use App\Service\OverpassService;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Psr\Log\LoggerInterface;



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
            $foundTrails = $trailRepository->findAll();
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
        Cloudinary $cloudinary,
        LoggerInterface $logger
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
                    try {
                        $image->move($this->getParameter('images_directory'), $newFilename);
                    } catch (FileException $e) {
                        $logger->error('Image upload failed', [
                            'error' => $e->getMessage(),
                            'file' => $newFilename,
                            'directory' => $this->getParameter('images_directory')
                        ]);
                        $this->addFlash('error', 'Erreur lors du téléchargement de l\'image. Vérifiez vos permissions.');
                        return $this->redirectToRoute('app_trail_new');
                    }
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
                            ]
                        );
                        // // Enregistre dans l'entité Photo créé le nom de fichier nécessaire pour appeler l'image
                        $photo->setCdnLink($publicId);

                        // Si l'upload Cloudinary fonctionne alors je supprime la version locale
                        $filePath = $this->getParameter('images_directory') . '/' . $newFilename;
                        if (file_exists($filePath)) {
                            try {
                                unlink($filePath);
                            } catch (\Throwable $e) {
                                $logger->warning('Failed to delete local file after Cloudinary upload', [
                                    'file' => $filePath,
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }
                    } catch (\Throwable $e) {
                        // Si l'upload Cloudinary ne fonctionne pas j'affiche l'erreur
                        $logger->error('Cloudinary upload failed', [
                            'error' => $e->getMessage(),
                            'file' => $newFilename
                        ]);

                        // J'affiche un message d'erreur
                        $this->addFlash('warning', 'Image non uploadée. Veuillez réessayer.');
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
                try {
                    $file->move($this->getParameter('gpx_directory'), $newFilename);
                } catch (FileException $e) {
                    $logger->error('File upload failed', [
                        'error' => $e->getMessage(),
                        'file' => $newFilename,
                        'directory' => $this->getParameter('gpx_directory')
                    ]);
                    $this->addFlash('error', 'Erreur lors du téléchargement du fichier GPX. Vérifiez vos permissions.');
                    return $this->redirectToRoute('app_trail_new');
                }
                $trail->setGpxFile($newFilename);

                // On récupère le fichier récemment enregistré
                $filePath = $this->getParameter('gpx_directory') . '/' . $newFilename;

                // On lit le fichier GPX, on le parse (via méthode dans) GPXService
                $infos = $gpx->parse($filePath);
                if ($infos) {
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

            // Prépare l'objet à enregistrer
            $em->persist($trail);


            try {
                // On essaye de l'ajouter à la base de données
                $em->flush();
                $this->addFlash('success', 'Itinéraire créé avec succès');
                return $this->redirectToRoute('app_trail_index');
            } catch (UniqueConstraintViolationException $e) {
                // Exception levée s'il y a un doublon
                $logger->warning('Trail already exists', [
                    'user_id' => $user->getId(),
                    'error' => $e->getMessage()
                ]);
                $this->addFlash('warning', 'Cet itinéraire existe déjà.');
                return $this->redirectToRoute('app_trail_new');
            } catch (\Exception $e) {
                // Exception pour d'autres erreurs SQL
                $logger->error('Failed to save trail', [
                    'user_id' => $user->getId(),
                    'error' => $e->getMessage(),
                    'code' => $e->getCode()
                ]);
                $this->addFlash('warning', 'Une erreur est survenue lors de l\'enregistrement. Veuillez réessayer.');
                return $this->redirectToRoute('app_home');
            }
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
        Cloudinary $cloudinary,
        LoggerInterface $logger
    ): Response {
        $nextWalks = $walkRepository->findNextByTrail($trail);

        $photo = new Photo();
        $photo->setTrail($trail);
        $photo->setUser($this->getUser());
        $form = $this->createForm(PhotoType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->addFlash('info', 'Formulaire soumis');

            if (!$form->isValid()) {
                $this->addFlash('error', 'Formulaire invalide : ' . (string) $form->getErrors(true));
                return $this->redirectToRoute('app_trail_show', ['id' => $trail->getId()]);
            }

            $images = $form->get('name')->getData();

            if (!$images) {
                $this->addFlash('warning', 'Aucune image détectée');
                return $this->redirectToRoute('app_trail_show', ['id' => $trail->getId()]);
            }

            foreach ($images as $image) {
                $original = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $safe = $slugger->slug($original);
                $newFilename = $safe . '-' . uniqid() . '.' . $image->guessExtension();

                try {
                    $image->move($this->getParameter('images_directory'), $newFilename);
                } catch (FileException $e) {
                    $logger->error('Image upload failed', [
                        'error' => $e->getMessage(),
                        'file' => $newFilename,
                        'directory' => $this->getParameter('images_directory')
                    ]);
                    $this->addFlash('error', 'Erreur lors du téléchargement de l\'image. Vérifiez vos permissions.');
                    return $this->redirectToRoute('app_trail_new');
                }

                $photo = new Photo();
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
                            'eager' => [[
                                'width' => 300,
                                'height' => 160,
                                'crop' => 'fit',
                                'quality' => 'auto:good',
                                'fetch_format' => 'webp',
                            ]],
                        ]
                    );
                    // Enregistre dans l'entité Photo créé le nom de fichier nécessaire pour appeler l'image
                    $photo->setCdnLink($publicId);
                } catch (\Throwable $e) {

                    $this->addFlash('error', 'Échec Cloudinary : ' . $e->getMessage());
                }
                $photo->setUser($this->getUser());
                $photo->setTrail($trail);
                $trail->addPhoto($photo);
                try {
                    // On essaye de l'ajouter à la base de données
                    $em->flush();
                    $this->addFlash('success', 'Photos ajoutées avec succès');
                    return $this->redirectToRoute('app_trail_show', ['id' => $trail->getId()]);
                } catch (\Exception $e) {
                    // Exception pour erreurs SQL
                    $logger->error('Failed to save image', [
                        'error' => $e->getMessage(),
                        'code' => $e->getCode()
                    ]);
                    $this->addFlash('warning', 'Une erreur est survenue lors de l\'enregistrement. Veuillez réessayer.');
                    return $this->redirectToRoute('app_home');
                }
            }
        }
        return $this->render('trail/show.html.twig', [
            'trail' => $trail,
            'form'  => $form->createView(),
            'nextWalks' => $nextWalks
        ]);
    }

    #[Route('/{id}/edit', name: 'app_trail_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Trail $trail, EntityManagerInterface $entityManager, LoggerInterface $logger): Response
    {

        $form = $this->createForm(TrailType::class, $trail);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // On essaye de l'ajouter à la base de données
                $entityManager->flush();
                $this->addFlash('notice', 'Vos modifications sont enregistrées !');
                return $this->redirectToRoute('app_trail_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                // Exception pour erreurs SQL
                $logger->error('Failed to save trail', [
                    'error' => $e->getMessage(),
                    'code' => $e->getCode()
                ]);
                $this->addFlash('warning', 'Une erreur est survenue lors de l\'enregistrement. Veuillez réessayer.');
            }
        }

        return $this->render('trail/edit.html.twig', [
            'trail' => $trail,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/inactive', name: 'app_trail_inactive', methods: ['POST'])]
    public function inactive(Request $request, Trail $trail, EntityManagerInterface $entityManager, LoggerInterface $logger): Response
    {
        $submittedToken = $request->request->get('_token');

        if ($this->isCsrfTokenValid('inactive' . $trail->getId(), $submittedToken)) {
            $trail->setStatus("Inactive");
            try {
                $entityManager->flush();
                $this->addFlash('warning', 'Votre itinéraire a été supprimé');
            } catch (\Exception $e) {
                $logger->error('Failed to save trail', [
                    'error' => $e->getMessage(),
                    'code' => $e->getCode()
                ]);
                $this->addFlash('error', 'Erreur lors de la désactivation de l\'itinéraire.');
            }
        } else {
            $this->addFlash('warning', 'Jeton CSRF invalide, action annulée.');
        }

        return $this->redirectToRoute('app_home');
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/import-from-overpass', name: 'app_trail_import_overpass', methods: ['GET', 'POST'])]
    public function importFromOverpass(
        Request $request,
        OverpassService $overpassService,
        GeocodingService $geocodingService,
        GpxService $gpxService,
        EntityManagerInterface $em,
        LoggerInterface $logger
    ): Response {
        $importedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;
        $errors = [];

        // Si le formulaire est soumis (importation confirmée)
        if ($request->isMethod('POST')) {
            $maxDistance = (float) $request->request->get('max_distance', 15.0);
            $city = $request->request->get('city');
            $adminUser = $this->getUser();

            if (!$adminUser instanceof User) {
                $this->addFlash('error', 'Utilisateur non authentifié.');
                return $this->redirectToRoute('app_home');
            }

            // Géocoder la ville pour obtenir les coordonnées
            if (!$city) {
                $this->addFlash('error', 'Veuillez renseigner une ville.');
                return $this->redirectToRoute('admin_user_index');
            }

            $cityCoords = $geocodingService->geocode($city);
            if (!$cityCoords) {
                $this->addFlash('error', "Impossible de localiser la ville '{$city}'.");
                return $this->redirectToRoute('admin_user_index');
            }

            $logger->info('Début de l\'importation depuis Overpass', [
                'city' => $city,
                'lat' => $cityCoords['lat'],
                'lon' => $cityCoords['lon'],
                'max_distance' => $maxDistance
            ]);

            // Récupérer les itinéraires dans un rayon de 15 km autour de la ville
            $osmTrails = $overpassService->fetchHikingTrailsAround(
                $cityCoords['lat'],
                $cityCoords['lon'],
                30000, // 30 km en mètres
                $maxDistance
            );
            $logger->info('Itinéraires récupérés depuis Overpass', ['count' => count($osmTrails)]);

            $gpxDirectory = $this->getParameter('gpx_directory');

            // Vérifier que le dossier existe
            if (!is_dir($gpxDirectory)) {
                mkdir($gpxDirectory, 0777, true);
            }

            foreach ($osmTrails as $osmTrail) {
                try {
                    // Vérifier si l'itinéraire existe déjà (par nom)
                    $existingTrail = $em->getRepository(Trail::class)->findOneBy([
                        'name' => $osmTrail['name']
                    ]);

                    if ($existingTrail) {
                        $skippedCount++;
                        $logger->info('Itinéraire déjà existant, ignoré', ['name' => $osmTrail['name']]);
                        continue;
                    }

                    // Générer le fichier GPX
                    $gpxFilename = 'osm_' . $osmTrail['osm_id'] . '_' . uniqid() . '.gpx';
                    $gpxPath = $gpxDirectory . '/' . $gpxFilename;

                    if (!$overpassService->generateGpxFile($osmTrail, $gpxPath)) {
                        $errorCount++;
                        $errors[] = "Impossible de générer le GPX pour '{$osmTrail['name']}'";
                        $logger->error('Échec génération GPX', ['trail' => $osmTrail['name']]);
                        continue;
                    }

                    // Créer l'entité Trail
                    $trail = new Trail();
                    $trail->setName($osmTrail['name']);
                    $trail->setGpxFile($gpxFilename);
                    $trail->setInputMode('gpx');
                    $trail->setCircuitType($osmTrail['circuit_type']);
                    $trail->setUser($adminUser);

                    // Mapper la difficulté si disponible
                    if ($osmTrail['difficulty']) {
                        $trail->setDifficulty($osmTrail['difficulty']);
                    }

                    // Utiliser le GpxService pour parser le fichier et remplir automatiquement
                    // les coordonnées, distance, durée et adresses (via géocodage)
                    $gpxInfos = $gpxService->parse($gpxPath);
                    if ($gpxInfos) {
                        $trail->setDistance(round($gpxInfos['distance_m'] / 1000, 2));
                        $trail->setDuration(round($gpxInfos['duration_s'] / 60, 2));
                        $trail->setStartLat($gpxInfos['start']['lat']);
                        $trail->setStartLon($gpxInfos['start']['lon']);
                        $trail->setEndLat($gpxInfos['end']['lat']);
                        $trail->setEndLon($gpxInfos['end']['lon']);

                        // Géocodage inversé pour les adresses
                        if ($startAddress = $geocodingService->reverse($gpxInfos['start']['lat'], $gpxInfos['start']['lon'])) {
                            $trail->setStartAddress($startAddress['street'] ?: ($startAddress['label'] ?? ''));
                            $trail->setStartCity($startAddress['city'] ?? '');
                            $trail->setStartCode($startAddress['postcode'] ?? '');
                        }
                        if ($endAddress = $geocodingService->reverse($gpxInfos['end']['lat'], $gpxInfos['end']['lon'])) {
                            $trail->setEndAddress($endAddress['street'] ?: ($endAddress['label'] ?? ''));
                            $trail->setEndCity($endAddress['city'] ?? '');
                            $trail->setEndCode($endAddress['postcode'] ?? '');
                        }
                    }

                    // Persister l'entité
                    $em->persist($trail);
                    $importedCount++;
                    $logger->info('Itinéraire importé avec succès', ['name' => $osmTrail['name']]);
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = "Erreur pour '{$osmTrail['name']}': " . $e->getMessage();
                    $logger->error('Erreur lors de l\'importation', [
                        'trail' => $osmTrail['name'],
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Sauvegarder tous les itinéraires importés
            try {
                $em->flush();
                $this->addFlash('success', "{$importedCount} itinéraire(s) importé(s) avec succès.");
                if ($skippedCount > 0) {
                    $this->addFlash('info', "{$skippedCount} itinéraire(s) déjà existant(s), ignoré(s).");
                }
                if ($errorCount > 0) {
                    $this->addFlash('warning', "{$errorCount} erreur(s) rencontrée(s):{$errors}");
                }
            } catch (\Exception $e) {
                $logger->error('Échec de la sauvegarde en base', ['error' => $e->getMessage()]);
                $this->addFlash('error', 'Erreur lors de la sauvegarde en base de données.');
            }

            return $this->redirectToRoute('app_trail_index');
        }

        // Affichage du formulaire de configuration
        return $this->render('trail/import_overpass.html.twig', [
            'imported_count' => $importedCount,
            'skipped_count' => $skippedCount,
            'error_count' => $errorCount,
            'errors' => $errors
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}/delete', name: 'app_trail_delete', methods: ['POST'])]
    public function delete(Request $request, Trail $trail, EntityManagerInterface $entityManager, LoggerInterface $logger): Response
    {
        if ($this->isCsrfTokenValid('delete' . $trail->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($trail);
            try {
                $entityManager->flush();
                $this->addFlash('success', "L'itinéraire a été supprimé");
            } catch (ForeignKeyConstraintViolationException $e) {
                $this->addFlash('warning', 'Impossible de supprimer : cet itinéraire est utilisé dans des balades.');
            } catch (\Exception $e) {
                $this->addFlash('warning', 'Erreur lors de la suppression.');
                $logger->error('Failed to save trail', [
                    'error' => $e->getMessage(),
                    'code' => $e->getCode()
                ]);
            }
        } else {
            $this->addFlash('warning', 'Jeton CSRF invalide, action annulée.');
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
