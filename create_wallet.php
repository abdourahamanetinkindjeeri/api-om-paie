<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Wallet;

$user = User::where('telephone', '771234567')->first();

if ($user) {
    if (!$user->wallet) {
        $wallet = Wallet::create([
            'user_id' => $user->id,
            'balance' => 50000.00
        ]);
        echo "Wallet créé pour l'utilisateur {$user->telephone} avec un solde de 50000 FCFA\n";
        echo "User ID: {$user->id}\n";
        echo "Wallet ID: {$wallet->id}\n";
    } else {
        echo "L'utilisateur a déjà un wallet avec un solde de {$user->wallet->balance} FCFA\n";
    }
} else {
    echo "Utilisateur non trouvé\n";
}
