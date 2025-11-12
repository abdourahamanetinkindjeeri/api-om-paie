<?php

require __DIR__ . '/vendor/autoload.php';

use Twilio\Rest\Client;

// Charger les variables d'environnement
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$sid = $_ENV['TWILIO_SID'];
$token = $_ENV['TWILIO_AUTH_TOKEN'];
$from = $_ENV['TWILIO_PHONE'];

echo "=== Test de configuration Twilio ===\n\n";
echo "SID: " . $sid . "\n";
echo "From: " . $from . "\n";
echo "Token présent: " . (!empty($token) ? "Oui" : "Non") . "\n\n";

try {
    $client = new Client($sid, $token);

    echo "Client Twilio créé avec succès!\n\n";

    // Test d'envoi (commenté pour éviter les frais)
    // Décommentez et remplacez le numéro pour tester
    /*
    $message = $client->messages->create(
        '+221XXXXXXXXX', // Remplacer par votre numéro
        [
            'from' => $from,
            'body' => 'Test OM-Paie: Votre code est 123456'
        ]
    );

    echo "Message envoyé!\n";
    echo "SID: " . $message->sid . "\n";
    echo "Status: " . $message->status . "\n";
    */
} catch (\Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
}
