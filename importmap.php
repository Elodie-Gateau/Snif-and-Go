<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
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
    'profile' => [
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
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    '@hotwired/turbo' => [
        'version' => '7.3.0',
    ],
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
    'tom-select/dist/css/tom-select.default.css' => [
        'version' => '2.4.3',
        'type' => 'css',
    ],
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
    'leaflet' => [
        'version' => '1.9.4',
    ],
    'leaflet-gpx' => [
        'version' => '1.7.0',
    ],
    '@symfony/ux-autocomplete' => [
        'version' => '2.31.0',
    ],
    'tom-select/dist/css/tom-select.default.min.css' => [
        'version' => '2.4.3',
        'type' => 'css',
    ],
];
