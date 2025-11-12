<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuration des paiements OM-Paie
    |--------------------------------------------------------------------------
    |
    | Ce fichier contient les paramètres de configuration pour le système
    | de paiement OM-Paie, incluant les limites de montants et les règles
    | de validation.
    |
    */

    'payment' => [
        /*
        |--------------------------------------------------------------------------
        | Limites de montant
        |--------------------------------------------------------------------------
        |
        | Définit les montants minimum et maximum autorisés pour les transactions
        | de paiement dans le système. Montants en FCFA.
        |
        */
        'amount' => [
            'min' => env('OMPAIE_MIN_AMOUNT', 100),
            'max' => env('OMPAIE_MAX_AMOUNT', 2000000),
        ],

        /*
        |--------------------------------------------------------------------------
        | Validation du code marchand
        |--------------------------------------------------------------------------
        |
        | Format attendu pour le code marchand : lettres majuscules et chiffres
        | avec une longueur entre min_length et max_length caractères.
        |
        */
        'merchant_code' => [
            'pattern' => '/^[A-Z0-9]{6,20}$/',
            'min_length' => 6,
            'max_length' => 20,
        ],

        /*
        |--------------------------------------------------------------------------
        | Validation du montant
        |--------------------------------------------------------------------------
        |
        | Nombre de décimales autorisées pour les montants.
        |
        */
        'amount_decimals' => 2,

        /*
        |--------------------------------------------------------------------------
        | Limites de longueur des champs
        |--------------------------------------------------------------------------
        |
        */
        'description_max_length' => 255,
        'reference_externe_max_length' => 100,
    ],

    'transfer' => [
        /*
        |--------------------------------------------------------------------------
        | Limites de montant pour les transferts
        |--------------------------------------------------------------------------
        |
        | Définit les montants minimum et maximum autorisés pour les transferts
        | entre utilisateurs. Montants en FCFA.
        |
        */
        'amount' => [
            'min' => env('OMPAIE_TRANSFER_MIN_AMOUNT', 100),
            'max' => env('OMPAIE_TRANSFER_MAX_AMOUNT', 2000000),
        ],

        /*
        |--------------------------------------------------------------------------
        | Validation du montant
        |--------------------------------------------------------------------------
        |
        */
        'amount_decimals' => 2,
    ],

    'registration' => [
        /*
        |--------------------------------------------------------------------------
        | Validation des champs d'inscription
        |--------------------------------------------------------------------------
        |
        */
        'nom_max_length' => 100,
        'prenom_max_length' => 100,
        'adresse_max_length' => 255,
        'email_max_length' => 255,

        /*
        |--------------------------------------------------------------------------
        | Code OTP
        |--------------------------------------------------------------------------
        |
        */
        'otp_digits' => 4,

        /*
        |--------------------------------------------------------------------------
        | Types de pièces d'identité acceptés
        |--------------------------------------------------------------------------
        |
        */
        'allowed_id_types' => ['cin', 'passport'],
    ],

    'otp' => [
        /*
        |--------------------------------------------------------------------------
        | Messages OTP
        |--------------------------------------------------------------------------
        |
        */
        'messages' => [
            'registration' => "Votre code de vérification OM-Paie est: {code}. Ce code à 6 chiffres expire dans 5 minutes.",
            'password_reset' => "Votre code de réinitialisation OM-Paie est: {code}. Ce code à 6 chiffres expire dans 5 minutes.",
            'login' => "Votre code de connexion OM-Paie est: {code}. Ce code à 6 chiffres expire dans 5 minutes.",
        ],

        /*
        |--------------------------------------------------------------------------
        | Durée d'expiration par défaut (minutes)
        |--------------------------------------------------------------------------
        |
        */
        'default_expiry_minutes' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Durée de cache pour certaines données critiques (en minutes).
    |
    */
    'cache' => [
        'merchant_ttl' => env('OMPAIE_MERCHANT_CACHE_TTL', 60), // 1 heure par défaut
    ],
];
