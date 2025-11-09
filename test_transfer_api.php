<?php

// Script de test pour l'API de transfert
// Exécuter avec : php test_transfer_api.php

require_once 'vendor/autoload.php';

use App\Models\User;
use App\Services\TransferService;
use App\Repositories\TransferRepository;
use Illuminate\Support\Facades\DB;

// Simulation d'un test simple
echo "=== Test de l'API de Transfert ===\n\n";

// 1. Test de vérification d'existence de numéro
echo "1. Test de vérification d'existence de numéro\n";

try {
    $transferRepo = new TransferRepository(new \App\Models\Transaction());
    $transferService = new TransferService($transferRepo);

    // Test avec un numéro existant
    $exists = $transferService->checkUserExists('221771234567');
    echo "Numéro 221771234567 existe : " . ($exists ? "OUI" : "NON") . "\n";

    // Test avec un numéro inexistant
    $exists = $transferService->checkUserExists('221999999999');
    echo "Numéro 221999999999 existe : " . ($exists ? "OUI" : "NON") . "\n";
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
}

echo "\n";

// 2. Test de récupération de solde
echo "2. Test de récupération de solde\n";

try {
    $balance = $transferService->getUserBalance('221771234567');
    echo "Solde de 221771234567 : " . number_format($balance, 0, ',', ' ') . " FCFA\n";

    $balance = $transferService->getUserBalance('221771234568');
    echo "Solde de 221771234568 : " . number_format($balance, 0, ',', ' ') . " FCFA\n";
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
}

echo "\n";

// 3. Test de transfert (simulation)
echo "3. Test de transfert (221771234567 → 221771234568 : 5000 FCFA)\n";

try {
    // Afficher les soldes avant transfert
    $senderBalanceBefore = $transferService->getUserBalance('221771234567');
    $receiverBalanceBefore = $transferService->getUserBalance('221771234568');

    echo "Avant transfert :\n";
    echo "- Expéditeur (221771234567) : " . number_format($senderBalanceBefore, 0, ',', ' ') . " FCFA\n";
    echo "- Destinataire (221771234568) : " . number_format($receiverBalanceBefore, 0, ',', ' ') . " FCFA\n";

    // Effectuer le transfert
    $result = $transferService->transfer('221771234567', '221771234568', 5000);

    echo "\nTransfert effectué avec succès !\n";
    echo "Référence : " . $result['reference'] . "\n";
    echo "Montant : " . number_format($result['amount'], 0, ',', ' ') . " FCFA\n";

    echo "\nAprès transfert :\n";
    echo "- Expéditeur : " . number_format($result['sender']['new_balance'], 0, ',', ' ') . " FCFA\n";
    echo "- Destinataire : " . number_format($result['receiver']['new_balance'], 0, ',', ' ') . " FCFA\n";
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
}

echo "\n=== Fin des tests ===\n";
