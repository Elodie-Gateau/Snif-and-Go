<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Psr\Log\LoggerInterface;

final class OverpassService
{
    private const OVERPASS_API_URL = 'https://overpass-api.de/api/interpreter';

    public function __construct(
        private HttpClientInterface $http,
        private LoggerInterface $logger
    ) {}

    /**
     * Récupère les itinéraires de randonnée autour d'une position dans un rayon donné
     */
    public function fetchHikingTrailsAround(float $lat, float $lon, int $radiusMeters, float $maxDistanceKm = 20.0): array
    {
        $query = $this->buildOverpassQueryAround($lat, $lon, $radiusMeters);

        try {
            $response = $this->queryOverpass($query);
            return $this->parseOverpassResponse($response, $maxDistanceKm);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des itinéraires', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Récupère les itinéraires de randonnée en France de moins de 30km (ancienne méthode)
     */
    public function fetchHikingTrails(float $maxDistanceKm = 30.0): array
    {
        $query = $this->buildOverpassQuery();

        try {
            $response = $this->queryOverpass($query);
            return $this->parseOverpassResponse($response, $maxDistanceKm);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des itinéraires', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Construit la requête Overpass QL pour récupérer les routes autour d'une position
     */
    private function buildOverpassQueryAround(float $lat, float $lon, int $radiusMeters): string
    {
        return <<<OVERPASS
[out:json][timeout:15];
(
  relation["route"="hiking"](around:$radiusMeters,$lat,$lon);
);
out body 10;
>;
out skel qt;
OVERPASS;
    }

    /**
     * Construit la requête Overpass QL pour récupérer les routes de randonnée en Vendée
     * Limitée à 10 résultats pour éviter les timeouts
     */
    private function buildOverpassQuery(): string
    {
        return <<<OVERPASS
[out:json][timeout:15];
area["name"="Vendée"]["admin_level"="6"]->.vendee;
(
  relation["route"="hiking"](area.vendee);
);
out body 10;
>;
out skel qt;
OVERPASS;
    }

    /**
     * Effectue la requête vers l'API Overpass
     */
    private function queryOverpass(string $query): array
    {
        $response = $this->http->request('POST', self::OVERPASS_API_URL, [
            'body' => ['data' => $query],
            'timeout' => 30,
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Erreur API Overpass: ' . $response->getStatusCode());
        }

        return $response->toArray();
    }

    /**
     * Parse la réponse de l'API Overpass et filtre par distance
     */
    private function parseOverpassResponse(array $data, float $maxDistanceKm): array
    {
        $trails = [];
        $nodes = [];
        $ways = [];

        // Indexer les nodes et ways pour un accès rapide
        foreach ($data['elements'] as $element) {
            if ($element['type'] === 'node') {
                $nodes[$element['id']] = $element;
            } elseif ($element['type'] === 'way') {
                $ways[$element['id']] = $element;
            }
        }

        // Traiter les relations (itinéraires)
        foreach ($data['elements'] as $element) {
            if (
                $element['type'] === 'relation' &&
                isset($element['tags']['route']) &&
                in_array($element['tags']['route'], ['hiking', 'foot'])
            ) {

                $trail = $this->parseTrailRelation($element, $nodes, $ways);

                // Filtrer par distance
                if ($trail && $trail['distance_km'] > 0 && $trail['distance_km'] <= $maxDistanceKm) {
                    $trails[] = $trail;
                }
            }
        }

        return $trails;
    }

    /**
     * Parse une relation OSM représentant un itinéraire
     */
    private function parseTrailRelation(array $relation, array $nodes, array $ways): ?array
    {
        $tags = $relation['tags'] ?? [];
        $name = $tags['name'] ?? 'Itinéraire sans nom #' . $relation['id'];

        // Récupérer les coordonnées du tracé
        $coordinates = $this->extractCoordinates($relation, $nodes, $ways);

        if (empty($coordinates) || count($coordinates) < 2) {
            return null;
        }

        // Calculer la distance
        $distanceM = $this->calculateDistance($coordinates);

        // Déterminer si c'est une boucle
        $isLoop = $this->isLoop($coordinates);

        return [
            'osm_id' => $relation['id'],
            'name' => $name,
            'distance_km' => round($distanceM / 1000, 2),
            'distance_m' => $distanceM,
            'coordinates' => $coordinates,
            'difficulty' => $this->mapDifficulty($tags),
            'description' => $tags['description'] ?? null,
            'website' => $tags['website'] ?? null,
            'ref' => $tags['ref'] ?? null,
            'circuit_type' => $isLoop ? 'boucle' : 'linear',
            'start_lat' => $coordinates[0]['lat'],
            'start_lon' => $coordinates[0]['lon'],
            'end_lat' => $coordinates[count($coordinates) - 1]['lat'],
            'end_lon' => $coordinates[count($coordinates) - 1]['lon'],
        ];
    }

    /**
     * Extrait les coordonnées d'une relation
     */
    private function extractCoordinates(array $relation, array $nodes, array $ways): array
    {
        $coordinates = [];

        foreach ($relation['members'] ?? [] as $member) {
            if ($member['type'] === 'node' && isset($nodes[$member['ref']])) {
                $node = $nodes[$member['ref']];
                if (isset($node['lat']) && isset($node['lon'])) {
                    $coordinates[] = [
                        'lat' => $node['lat'],
                        'lon' => $node['lon']
                    ];
                }
            } elseif ($member['type'] === 'way' && isset($ways[$member['ref']])) {
                $way = $ways[$member['ref']];
                foreach ($way['nodes'] ?? [] as $nodeId) {
                    if (isset($nodes[$nodeId])) {
                        $node = $nodes[$nodeId];
                        if (isset($node['lat']) && isset($node['lon'])) {
                            $coordinates[] = [
                                'lat' => $node['lat'],
                                'lon' => $node['lon']
                            ];
                        }
                    }
                }
            }
        }

        return $coordinates;
    }

    /**
     * Calcule la distance totale d'un tracé (formule de Haversine)
     */
    private function calculateDistance(array $coordinates): float
    {
        if (count($coordinates) < 2) {
            return 0;
        }

        $distance = 0;
        for ($i = 1; $i < count($coordinates); $i++) {
            $distance += $this->haversine(
                $coordinates[$i - 1]['lat'],
                $coordinates[$i - 1]['lon'],
                $coordinates[$i]['lat'],
                $coordinates[$i]['lon']
            );
        }

        return $distance;
    }

    /**
     * Formule de Haversine pour calculer la distance entre deux points
     */
    private function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // en mètres

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Détermine si un tracé est une boucle (départ = arrivée)
     */
    private function isLoop(array $coordinates): bool
    {
        if (count($coordinates) < 2) {
            return false;
        }

        $first = $coordinates[0];
        $last = $coordinates[count($coordinates) - 1];

        // Distance entre premier et dernier point < 100m = boucle
        $distance = $this->haversine($first['lat'], $first['lon'], $last['lat'], $last['lon']);

        return $distance < 100;
    }

    /**
     * Mappe la difficulté OSM vers votre système (easy, medium, hard)
     */
    private function mapDifficulty(array $tags): ?string
    {
        // Vérifier les tags de difficulté
        $sacScale = $tags['sac_scale'] ?? null;
        $difficulty = $tags['difficulty'] ?? null;

        if ($sacScale) {
            return match ($sacScale) {
                'hiking', 'T1' => 'easy',
                'mountain_hiking', 'T2', 'T3' => 'medium',
                'demanding_mountain_hiking', 'alpine_hiking', 'T4', 'T5', 'T6' => 'hard',
                default => null
            };
        }

        if ($difficulty) {
            $diff = strtolower($difficulty);
            return match (true) {
                str_contains($diff, 'easy') || str_contains($diff, 'facile') => 'easy',
                str_contains($diff, 'medium') || str_contains($diff, 'moyen') => 'medium',
                str_contains($diff, 'hard') || str_contains($diff, 'difficile') => 'hard',
                default => null
            };
        }

        return null;
    }

    /**
     * Génère un fichier GPX à partir des coordonnées d'un itinéraire
     */
    public function generateGpxFile(array $trail, string $outputPath): bool
    {
        $name = htmlspecialchars($trail['name']);
        $coordinates = $trail['coordinates'];

        if (empty($coordinates)) {
            return false;
        }

        $gpx = new \DOMDocument('1.0', 'UTF-8');
        $gpx->formatOutput = true;

        // Racine GPX
        $gpxRoot = $gpx->createElement('gpx');
        $gpxRoot->setAttribute('version', '1.1');
        $gpxRoot->setAttribute('creator', 'SnifAndGo - OpenStreetMap');
        $gpxRoot->setAttribute('xmlns', 'http://www.topografix.com/GPX/1/1');
        $gpx->appendChild($gpxRoot);

        // Metadata
        $metadata = $gpx->createElement('metadata');
        $nameElement = $gpx->createElement('name', $name);
        $metadata->appendChild($nameElement);

        if (isset($trail['description'])) {
            $desc = $gpx->createElement('desc', htmlspecialchars($trail['description']));
            $metadata->appendChild($desc);
        }

        $gpxRoot->appendChild($metadata);

        // Track
        $track = $gpx->createElement('trk');
        $trackName = $gpx->createElement('name', $name);
        $track->appendChild($trackName);

        // Track segment
        $trackSegment = $gpx->createElement('trkseg');

        foreach ($coordinates as $coord) {
            $trackPoint = $gpx->createElement('trkpt');
            $trackPoint->setAttribute('lat', (string)$coord['lat']);
            $trackPoint->setAttribute('lon', (string)$coord['lon']);
            $trackSegment->appendChild($trackPoint);
        }

        $track->appendChild($trackSegment);
        $gpxRoot->appendChild($track);

        // Sauvegarder le fichier
        try {
            return $gpx->save($outputPath) !== false;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la génération du fichier GPX', [
                'path' => $outputPath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
