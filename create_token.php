<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

$user = User::where('telephone', '771234567')->first();

if ($user) {
    $token = $user->createToken('Test Payment Token');
    echo "Nouveau token créé pour l'utilisateur {$user->telephone}:\n";
    echo $token->accessToken . "\n";
    echo "Solde du wallet: {$user->wallet->balance} FCFA\n";
    echo "User ID: {$user->id}\n";
} else {
    echo "Utilisateur non trouvé\n";
}
