<?php

// Script de test pour l'API avec authentification
require_once 'bootstrap/app.php';

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

echo "=== Test de l'API de Transfert avec Authentification ===\n\n";

// 1. Connexion pour obtenir un token
echo "1. Connexion d'un utilisateur pour obtenir un token\n";

try {
    $user = User::where('numero', '221771234567')->first();

    if ($user) {
        echo "Utilisateur trouvé: {$user->nom} {$user->prenom}\n";

        // Créer un token d'accès personnel
        $token = $user->createToken('Test Token')->accessToken;
        echo "Token généré: " . substr($token, 0, 50) . "...\n";

        // Sauvegarder le token pour les tests suivants
        file_put_contents('test_token.txt', $token);
        echo "Token sauvegardé dans test_token.txt\n";
    } else {
        echo "Utilisateur non trouvé. Assurez-vous d'avoir exécuté le TransferTestSeeder.\n";
    }
} catch (Exception $e) {
    echo "Erreur lors de la génération du token : " . $e->getMessage() . "\n";
}

echo "\n2. Pour tester l'API, utilisez les commandes cURL suivantes :\n\n";

if (file_exists('test_token.txt')) {
    $token = file_get_contents('test_token.txt');

    echo "# Vérifier le solde\n";
    echo "curl -X GET 'http://localhost:8000/api/transfer/balance' \\\n";
    echo "  -H 'Authorization: Bearer {$token}' \\\n";
    echo "  -H 'Accept: application/json'\n\n";

    echo "# Vérifier si un numéro existe\n";
    echo "curl -X POST 'http://localhost:8000/api/transfer/check-number' \\\n";
    echo "  -H 'Authorization: Bearer {$token}' \\\n";
    echo "  -H 'Content-Type: application/json' \\\n";
    echo "  -H 'Accept: application/json' \\\n";
    echo "  -d '{\"numero\": \"221771234568\"}'\n\n";

    echo "# Effectuer un transfert\n";
    echo "curl -X POST 'http://localhost:8000/api/transfer' \\\n";
    echo "  -H 'Authorization: Bearer {$token}' \\\n";
    echo "  -H 'Content-Type: application/json' \\\n";
    echo "  -H 'Accept: application/json' \\\n";
    echo "  -d '{\"numero\": \"221771234568\", \"montant\": 1000}'\n\n";
}

echo "3. Démarrez le serveur Laravel avec: php artisan serve\n";
echo "4. Puis exécutez les commandes cURL ci-dessus dans un autre terminal.\n\n";

echo "=== Fin du script ===\n";
