<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

echo "🔍 Test envoi SMTP direct avec Symfony Mailer\n\n";

// Configuration
$username = env('MAIL_USERNAME', 'jeeridev@gmail.com');
$password = env('MAIL_PASSWORD');
$host = env('MAIL_HOST', 'smtp.gmail.com');
$port = env('MAIL_PORT', 587);

echo "📧 Configuration:\n";
echo "   Host: $host\n";
echo "   Port: $port\n";
echo "   Username: $username\n";
echo "   Password: " . (empty($password) ? "❌ VIDE" : "✅ Défini (" . strlen($password) . " caractères)") . "\n\n";

if (empty($password)) {
    echo "❌ MAIL_PASSWORD est vide!\n";
    exit(1);
}

try {
    echo "🔄 Création du transport SMTP...\n";
    
    // Retirer les espaces du mot de passe Gmail App Password
    $passwordClean = str_replace(' ', '', $password);
    
    // Création du DSN
    $dsn = sprintf(
        'smtp://%s:%s@%s:%d',
        urlencode($username),
        $passwordClean, // Mot de passe sans espaces
        $host,
        $port
    );
    
    echo "   DSN: smtp://" . urlencode($username) . ":***@$host:$port\n";
    echo "   Password clean length: " . strlen($passwordClean) . " caractères\n\n";
    
    $transport = Transport::fromDsn($dsn);
    $mailer = new Mailer($transport);
    
    echo "✅ Transport créé\n\n";
    
    echo "📨 Création et envoi de l'email de test...\n";
    
    $email = (new Email())
        ->from($username)
        ->to('abdourahamanetinkindjeeri99@gmail.com')
        ->subject('Test SMTP Direct - OM-Paie')
        ->text('Ceci est un test d\'envoi SMTP direct avec Symfony Mailer.');
    
    $mailer->send($email);
    
    echo "✅ Email envoyé avec succès à abdourahamanetinkindjeeri99@gmail.com\n";
    echo "\n🎉 Test réussi! Vérifiez votre boîte mail.\n";
    
} catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
    echo "❌ Erreur Transport SMTP:\n";
    echo "   Message: " . $e->getMessage() . "\n";
    if (method_exists($e, 'getDebug')) {
        echo "   Debug: " . ($e->getDebug() ?? 'N/A') . "\n";
    }
    exit(1);
} catch (\Exception $e) {
    echo "❌ Erreur:\n";
    echo "   Type: " . get_class($e) . "\n";
    echo "   Message: " . $e->getMessage() . "\n";
    echo "   Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
