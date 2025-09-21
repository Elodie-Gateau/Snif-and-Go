<?php

namespace App\Service;

use App\Service\DistanceService;

final class GpxService
{
    public function __construct(private DistanceService $distanceService) {}

    public function parse(string $gpxPath): ?array
    {
        // On récupère le fichier xml
        $xml = @simplexml_load_file($gpxPath);
        if (!$xml) {
            return null;
        }
        // Si le fichier existe, on déclare un namespace
        $xml->registerXPathNamespace('gpx', 'http://www.topografix.com/GPX/1/1');

        // on utilise SimpleXMLElement pour faire une requête XPath et récupérer les coordonnées des points
        $points = $xml->xpath('//gpx:trk/gpx:trkseg/gpx:trkpt');

        if (!$points || count($points) < 2) {
            // Si le type de point TrackPoint n'a pas marché on essaye les Route Point
            $points = $xml->xpath('//gpx:rte/gpx:rtept');
        }
        if (!$points || count($points) < 2) {
            return null;
        }
        // On récupère le premier et dernier point et leurs coordonnées
        $first = $points[0];
        $last = $points[count($points) - 1];

        $startLat = (float) $first['lat'];
        $startLon = (float) $first['lon'];
        $endLat   = (float) $last['lat'];
        $endLon   = (float) $last['lon'];

        // On essaye de calculer la distance avec la méthode OSRM
        $distanceOsrm = null;
        $route = $this->distanceService->osrmFootRoute($startLat, $startLon, $endLat, $endLon);
        if ($route) {
            $distanceOsrm = $route['distance_m'];
        }
        // si la méthode OSRM n'a pas fonctionné on applique une somme de distance calculées via la méthode haversine
        $distanceHav = 0;
        $prev = $points[0];
        $prevLat = (float) $prev['lat'];
        $prevLon = (float) $prev['lon'];

        for ($i = 1, $n = count($points); $i < $n; $i++) {
            $cur = $points[$i];
            $lat = (float) $cur['lat'];
            $lon = (float) $cur['lon'];

            $distanceHav += $this->distanceService->haversine($prevLat, $prevLon, $lat, $lon);
            $prevLat = $lat;
            $prevLon = $lon;
        }
        // On retient la méthode la plus cohérente : celle qui a la plus grande distance enregistrée
        if ($distanceOsrm > $distanceHav) {
            $distance = $distanceOsrm;
        } else {
            $distance = $distanceHav;
        }
        // On applique l'estimation de la durée à partir de la distance
        $duration = ($this->distanceService->estimateMinutesFromKm($distance / 1000)) * 60;

        return [
            'distance_m' => $distance,
            'duration_s' => $duration,
            'start' => ['lat' => (float)$first['lat'], 'lon' => (float)$first['lon']],
            'end'   => ['lat' => (float)$last['lat'], 'lon' => (float)$last['lon']],
        ];
    }
}
