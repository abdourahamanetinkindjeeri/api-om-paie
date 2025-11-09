<?php

// Script de debug pour la recherche d'utilisateurs
require_once 'vendor/autoload.php';

use App\Repositories\TransferRepository;
use App\Models\Transaction;
use App\Models\User;

echo "=== Debug de la recherche d'utilisateurs ===\n\n";

$transferRepo = new TransferRepository(new Transaction());

// Test de recherche pour différents formats
$numeros = ['771234567', '771234568', '771234569', '+221771234567', '+221771234568', '+221771234569'];

foreach ($numeros as $numero) {
    echo "Recherche pour: $numero\n";
    $user = $transferRepo->findUserByNumber($numero);

    if ($user) {
        echo "  ✅ Trouvé: {$user->nom} {$user->prenom} (ID: {$user->id}, Tel: {$user->telephone})\n";

        // Vérifier le wallet
        $wallet = $transferRepo->getUserWallet($user->id);
        if ($wallet) {
            echo "  💰 Wallet: {$wallet->balance} {$wallet->currency} (ID: {$wallet->id})\n";
        } else {
            echo "  ❌ Pas de wallet trouvé\n";
        }
    } else {
        echo "  ❌ Non trouvé\n";
    }
    echo "\n";
}

echo "=== Test de recherche directe en base ===\n";
$users = User::all(['id', 'nom', 'prenom', 'telephone']);
foreach ($users->take(10) as $user) {
    echo "- {$user->nom} {$user->prenom}: {$user->telephone} (ID: {$user->id})\n";
}

echo "\n=== Fin du debug ===\n";
