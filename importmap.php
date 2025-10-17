<?php

/**
 * Importmap de l'application Snif & Go — scoping par page
 */
return [
    // === Entrypoints par page ===
    'global' => [
        'path' => './assets/entrypoints/global.js',
        'entrypoint' => true,
    ],
    'home' => [
        'path' => './assets/entrypoints/home.js',
        'entrypoint' => true,
    ],
    'admin' => [
        'path' => './assets/entrypoints/admin.js',
        'entrypoint' => true,
    ],
    'admin' => [
        'path' => './assets/entrypoints/profile.js',
        'entrypoint' => true,
    ],
    'trail_show' => [
        'path' => './assets/entrypoints/trail_show.js',
        'entrypoint' => true,
    ],
    'trail_new' => [
        'path' => './assets/entrypoints/trail_new.js',
        'entrypoint' => true,
    ],
    'walk_new' => [
        'path' => './assets/entrypoints/walk_new.js',
        'entrypoint' => true,
    ],

    // === Stimulus / Turbo  ===
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    '@hotwired/turbo' => [
        'version' => '7.3.0',
    ],

    // === Tom Select ===
    'tom-select' => [
        'version' => '2.4.3',
    ],
    '@orchidjs/sifter' => [
        'version' => '1.1.0',
    ],
    '@orchidjs/unicode-variants' => [
        'version' => '1.1.2',
    ],
    'tom-select/dist/css/tom-select.bootstrap5.css' => [
        'version' => '2.4.3',
        'type' => 'css',
    ],

    // === Flatpickr ===
    'flatpickr' => [
        'version' => '4.6.13',
    ],
    'flatpickr/dist/flatpickr.min.css' => [
        'version' => '4.6.13',
        'type' => 'css',
    ],
    'flatpickr/dist/l10n/fr.js' => [
        'version' => '4.6.13',
    ],

    // === Leaflet ===
    'leaflet' => [
        'version' => 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
    ],
    'leaflet-gpx' => [
        'version' => 'https://cdn.jsdelivr.net/npm/leaflet-gpx@1.7.0/gpx.min.js',
    ],
];
