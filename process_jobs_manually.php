<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Contracts\OtpServiceInterface;

echo "🔄 Traitement manuel des jobs en attente...\n\n";

// Récupérer les jobs en attente
$jobs = DB::connection('mongodb')
    ->table('jobs')
    ->where('queue', 'notifications')
    ->whereNull('reserved_at')
    ->where('available_at', '<=', time())
    ->orderBy('id')
    ->get();

echo "📊 {$jobs->count()} job(s) à traiter\n\n";

$processed = 0;
$failed = 0;

foreach ($jobs as $job) {
    try {
        echo "🔧 Traitement du job {$job->id}...\n";

        // Décoder le payload
        $payload = json_decode($job->payload, true);
        $command = unserialize($payload['data']['command']);

        echo "  Type: {$payload['displayName']}\n";
        echo "  Tentatives: {$job->attempts}\n";

        // Marquer comme réservé
        DB::connection('mongodb')
            ->table('jobs')
            ->where('id', $job->id)
            ->update([
                'reserved_at' => time(),
                'attempts' => $job->attempts + 1
            ]);

        // Exécuter le job
        if ($command instanceof \App\Jobs\SendWelcomeOtpJob) {
            $otpService = app(OtpServiceInterface::class);
            $user = \App\Models\User::find($command->userId);

            if ($user) {
                echo "  Utilisateur: {$user->nom} {$user->prenom}\n";
                echo "  Email: {$command->identifier}\n";

                // Déterminer la destination
                $emailDest = filter_var($command->identifier, FILTER_VALIDATE_EMAIL)
                    ? $command->identifier
                    : ($user->email ?? 'jeeridev@gmail.com');

                echo "  Destination: {$emailDest}\n";

                // Envoyer l'OTP
                $success = $otpService->generateAndSend($emailDest, 'registration');

                if ($success) {
                    echo "  ✅ OTP envoyé avec succès\n";

                    // Supprimer le job
                    DB::connection('mongodb')
                        ->table('jobs')
                        ->where('id', $job->id)
                        ->delete();

                    $processed++;
                } else {
                    echo "  ❌ Échec d'envoi OTP\n";

                    // Réinitialiser reserved_at
                    DB::connection('mongodb')
                        ->table('jobs')
                        ->where('id', $job->id)
                        ->update(['reserved_at' => null]);

                    $failed++;
                }
            } else {
                echo "  ❌ Utilisateur non trouvé\n";

                // Supprimer le job (utilisateur introuvable)
                DB::connection('mongodb')
                    ->table('jobs')
                    ->where('id', $job->id)
                    ->delete();
            }
        }

        echo "\n";

    } catch (\Exception $e) {
        echo "  ❌ Erreur: {$e->getMessage()}\n\n";

        // Marquer comme failed si trop de tentatives
        if ($job->attempts >= 3) {
            DB::connection('mongodb')
                ->table('failed_jobs')
                ->insert([
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'connection' => 'database',
                    'queue' => $job->queue,
                    'payload' => $job->payload,
                    'exception' => $e->getMessage() . "\n" . $e->getTraceAsString(),
                    'failed_at' => now()
                ]);

            DB::connection('mongodb')
                ->table('jobs')
                ->where('id', $job->id)
                ->delete();
        } else {
            // Réinitialiser pour retry
            DB::connection('mongodb')
                ->table('jobs')
                ->where('id', $job->id)
                ->update([
                    'reserved_at' => null,
                    'available_at' => time() + 60 // Retry dans 1 minute
                ]);
        }

        $failed++;
    }
}

echo "\n📈 Résumé:\n";
echo "  ✅ Traités: {$processed}\n";
echo "  ❌ Échoués: {$failed}\n";
