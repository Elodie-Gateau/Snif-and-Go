<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;


final class GeocodingService
{
    public function __construct(private HttpClientInterface $http, private LoggerInterface $logger) {}


    public function geocode(string $address): ?array
    {
        // On récupère l'adresse saisie et on supprime les espace indésirables
        $q = trim($address);
        if ($q === '') return null;

        // On fait l'appel API data gouv
        $data = $this->http->request('GET', 'https://api-adresse.data.gouv.fr/search/', [
            'query' => ['q' => $q, 'limit' => 1],
        ])->toArray(false);

        // On récupère la première donnée trouvée, on vérifie qu'elle existe
        $result = $data['features'][0] ?? null;
        if (!$result) return null;
        // On traduit les résultats en coordonnées
        [$lon, $lat] = $result['geometry']['coordinates'];
        return [
            'lat' => (float)$lat,
            'lon' => (float)$lon,
            'label' => $result['properties']['label'] ?? $q,
        ];
    }

    public function reverse(float $lat, float $lon): ?array
    {
        // On liste les différentes options de recherche à faire : précis (la rue), large (l'API choisit) ou minimal (municipalité)
        $tries = [['type' => 'street'], [], ['type' => 'municipality']];

        foreach ($tries as $extra) {
            // On construit la recherche avec les coordonnées et le type d'option à tester
            $query = array_merge(['lat' => $lat, 'lon' => $lon], $extra);

            // On essaye de faire la requête API
            try {
                $data = $this->http->request('GET', 'https://api-adresse.data.gouv.fr/reverse/', [
                    'query'   => $query,
                    'timeout' => 5,
                ])->toArray(false);
            } catch (\Throwable) {
                continue;
            }
            // On récupère la première donnée trouvée, si elle existe on récupère les informations
            $feature = $data['features'][0] ?? null;
            if ($feature) {
                $result = $feature['properties'] ?? [];
                return [
                    'label'    => $result['label']    ?? null,
                    'street'   => $result['name']     ?? null,
                    'city'     => $result['city']     ?? ($result['municipality'] ?? null),
                    'postcode' => $result['postcode'] ?? null,
                ];
            }
        }

        // Si la première recherche échoue on tente de récupérer au moins la commune sur une autre API
        try {
            $communes = $this->http->request('GET', 'https://geo.api.gouv.fr/communes', [
                'query' => [
                    'lat'    => $lat,
                    'lon'    => $lon,
                    'fields' => 'nom,codesPostaux',
                    'format' => 'json',
                    'geometry' => 'centre',
                ],
                'timeout' => 5,
            ])->toArray(false);

            $commune = $communes[0] ?? null;
            if ($commune) {
                // On enregistre la ville et code postal
                $city = $commune['nom'] ?? null;
                $postcode = null;
                if (!empty($commune['codesPostaux']) && is_array($commune['codesPostaux'])) {
                    // s'il y a plusieurs CP, on prend le premier
                    $postcode = $c['codesPostaux'][0] ?? null;
                }
                return [
                    'label'    => $city,
                    'street'   => null,
                    'city'     => $city,
                    'postcode' => $postcode,
                ];
            }
        } catch (\Throwable $e) {
            $this->logger->warning('Erreur API : ' . $e->getMessage());
            // Si rien est trouvé on return null
        }
        return null;
    }
}
