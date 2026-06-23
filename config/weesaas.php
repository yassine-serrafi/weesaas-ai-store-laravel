<?php

return [

    /*
    | Clé historique de chiffrement des valeurs `settings` (AES-256-CBC).
    | NE PAS modifier si la base contient des valeurs chiffrées importées.
    */
    'legacy_encryption_key' => env('WEESAAS_LEGACY_ENCRYPTION_KEY', ''),

    /* Langue boutique par défaut (surchargée par settings.langue_defaut). */
    'default_lang' => env('WEESAAS_DEFAULT_LANG', 'fr'),

    /* Intégrations IA (peuvent aussi être stockées chiffrées dans settings). */
    'ai' => [
        'gemini' => [
            'key' => env('GEMINI_API_KEY', ''),
            'url' => env('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1/models/'),
        ],
        'openai' => [
            'key' => env('OPENAI_API_KEY', ''),
            'url' => env('OPENAI_API_URL', 'https://api.openai.com/v1/'),
        ],
    ],

    /* Rate-limit commande : nb max par IP sur la fenêtre (minutes). */
    'order_rate_limit' => [
        'max'            => 5,
        'window_minutes' => 15,
    ],

    /*
    | File d'attente : démarrage automatique d'un worker à la volée.
    | Quand `auto_worker` est actif, chaque génération de produit lance un worker
    | détaché (queue:work --stop-when-empty) qui traite le job puis s'arrête —
    | pas besoin de garder « php artisan queue:work » ouvert manuellement.
    | `php_binary` doit pointer vers php.exe CLI (sous Apache, PHP_BINARY ne l'est pas).
    */
    'queue' => [
        'auto_worker' => filter_var(env('WEESAAS_AUTO_WORKER', true), FILTER_VALIDATE_BOOL),
        'php_binary'  => env('WEESAAS_PHP_BINARY', PHP_BINARY),
    ],
];
