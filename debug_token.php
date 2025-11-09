<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Laravel\Passport\Token;

// Test du token
$bearerToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiJhMDRmZjNhOC0wMTkyLTQ0YjAtOTQ1Ny1iNzJhNzM4YTFhMGIiLCJqdGkiOiI1MWYxYTQ0NmU5ZDlhODgxYzQ5OWEyZDQ1YjU3MWJkOThkYzE3M2M5YWYzOTQwMjJlMDRmNTU4OWQyZDJhZDEzNmUyMmE2Y2U5Y2U3ZGYxMCIsImlhdCI6MTc2MjY4Nzk0OC40OTU3MjksIm5iZiI6MTc2MjY4Nzk0OC40OTU3MzgsImV4cCI6MTc5NDIyMzk0Ny42OTgxNDEsInN1YiI6ImEwNGZmM2UzLWU4YzAtNDkzZi04ZTQ5LTZiMmQ5ZjkyOTRiNSIsInNjb3BlcyI6W119';

try {
    // Décoder le JWT
    $jwt_parts = explode('.', $bearerToken);
    $payload = json_decode(base64_decode($jwt_parts[1]), true);

    echo "=== DEBUG TOKEN ===\n";
    echo "User ID from token: " . $payload['sub'] . "\n";

    // Trouver l'utilisateur par ID
    $user = User::find($payload['sub']);
    if ($user) {
        echo "User found: {$user->telephone}\n";
        echo "User wallet exists: " . ($user->wallet ? 'Yes' : 'No') . "\n";
        if ($user->wallet) {
            echo "Wallet balance: {$user->wallet->balance}\n";
            echo "Wallet ID: {$user->wallet->id}\n";
        }
    } else {
        echo "User not found!\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
