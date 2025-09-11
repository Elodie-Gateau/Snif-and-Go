<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class DistanceService
{
    public function __construct(private HttpClientInterface $http) {}

    public function osrmFootRoute(float $lat1, float $lon1, float $lat2, float $lon2): ?array
    {
        // On construit l'URL avec les coordonnées de départ et d'arrivée
        $url = sprintf(
            'https://router.project-osrm.org/route/v1/foot/%F,%F;%F,%F',
            $lon1,
            $lat1,
            $lon2,
            $lat2
        );
        // On envoie la requête à l'API OSRM sans géométrie détaillé, ni plusieurs routes ni de liste détaillée d'instructions
        $data = $this->http->request('GET', $url, [
            'query' => ['overview' => 'false', 'alternatives' => 'false', 'steps' => 'false'],
        ])->toArray(false); #On converti les données JSON récupérées en tableau PHP

        $route = $data['routes'][0] ?? null;
        if (!$route) return null;

        return [
            'distance_m' => (int) round($route['distance'] ?? 0),
            // 'duration_s' => (int) round($route['duration'] ?? 0),
        ];
    }

    public function haversine(float $lat1, float $lon1, float $lat2, float $lon2): int
    {
        $R = 6371000; # Rayon de la Terre
        $dLat = deg2rad($lat2 - $lat1); #Conversion en radian
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        return (int) round(2 * $R * atan2(sqrt($a), sqrt(1 - $a))); # Formule de calcul de distance à vol d'oiseau
    }

    public function estimateMinutesFromKm(float $distanceKm, float $speedKmh = 4.5): int
    {
        // On prend en valeur par défaut 4,5 km/h en vitesse de marche à pied
        if ($distanceKm <= 0 || $speedKmh <= 0) return 0;
        return (int) round(($distanceKm / $speedKmh) * 60);
    }
}
