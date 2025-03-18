<?php

return [
    'paths' => ['api/*'],

    'allowed_methods' => ['*'], // Autoriser toutes les méthodes HTTP

    'allowed_origins' => [env('FRONTEND_URLS', 'http://localhost:3000')], // Spécifiez exactement l'origine frontale ici

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'], // Autoriser tous les en-têtes

    'exposed_headers' => ['Set-Cookie', 'Content-Disposition'],

    'max_age' => 0,

    'supports_credentials' => true,
];
