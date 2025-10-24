<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Psr\Log\LoggerInterface;


final class DistanceService
{
    public function __construct(private HttpClientInterface $http, private LoggerInterface $logger) {}

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
        // On envoie la requête à l'API OSRM 
        try {
            $data = $this->http->request('GET', $url, [
                'query' => ['overview' => 'false', 'alternatives' => 'false', 'steps' => 'false'],
                'timeout'      => 4.0,
                'max_duration' => 6.0,
                'headers'      => ['User-Agent' => 'snif-and-go/1.0'],
            ])->toArray(false);

            $route = $data['routes'][0] ?? null;
            if (!$route) return null;

            return [
                'distance_m' => (int) round($route['distance'] ?? 0),
            ];
        } catch (TransportExceptionInterface $e) {
            $this->logger->warning('OSRM route API failed', [
                'error'       => $e->getMessage(),
                'coordinates' => "$lat1,$lon1 -> $lat2,$lon2",
                'exception'   => get_class($e),
            ]);
            return [
                'distance_m' => $this->haversine($lat1, $lon1, $lat2, $lon2),
                'source'     => 'haversine',
                'note'       => 'OSRM indisponible (timeout)',
            ];
        }
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
