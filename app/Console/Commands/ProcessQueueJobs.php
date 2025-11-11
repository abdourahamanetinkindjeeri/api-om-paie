<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\Contracts\OtpServiceInterface;
use App\Models\User;
use Illuminate\Support\Str;

class ProcessQueueJobs extends Command
{
    protected $signature = 'queue:process-mongodb 
                            {--queue=notifications : La queue à traiter}
                            {--once : Ne traiter qu\'un seul job}
                            {--sleep=3 : Temps d\'attente entre les vérifications (secondes)}';

    protected $description = 'Traite les jobs de la queue MongoDB';

    public function handle(OtpServiceInterface $otpService)
    {
        $queue = $this->option('queue');
        $once = $this->option('once');
        $sleep = (int) $this->option('sleep');

        $this->info("🔄 Démarrage du worker pour la queue '{$queue}'");

        $processed = 0;

        do {
            $job = $this->getNextJob($queue);

            if ($job) {
                try {
                    $this->processJob($job, $otpService);
                    $processed++;

                    if ($once) {
                        break;
                    }
                } catch (\Exception $e) {
                    $this->error("❌ Erreur: {$e->getMessage()}");
                }
            } else {
                if ($once) {
                    $this->info("Aucun job disponible");
                    break;
                }

                // Attendre avant de vérifier à nouveau
                sleep($sleep);
            }
        } while (!$once);

        if ($processed > 0) {
            $this->info("✅ {$processed} job(s) traité(s)");
        }

        return 0;
    }

    protected function getNextJob(string $queue)
    {
        return DB::connection('mongodb')
            ->table('jobs')
            ->where('queue', $queue)
            ->whereNull('reserved_at')
            ->where('available_at', '<=', time())
            ->orderBy('id')
            ->first();
    }

    protected function processJob($job, OtpServiceInterface $otpService)
    {
        // Décoder le payload
        $payload = json_decode($job->payload, true);
        $command = unserialize($payload['data']['command']);

        $this->line("Processing: {$payload['displayName']}");

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
            $user = User::find($command->userId);

            if (!$user) {
                $this->warn("⚠️  Utilisateur non trouvé: {$command->userId}");
                $this->deleteJob($job->id);
                return;
            }

            // Déterminer la destination
            $emailDest = filter_var($command->identifier, FILTER_VALIDATE_EMAIL)
                ? $command->identifier
                : ($user->email ?? 'jeeridev@gmail.com');

            // Envoyer l'OTP
            $success = $otpService->generateAndSend($emailDest, 'registration');

            if ($success) {
                $this->info("  ✅ OTP envoyé à {$emailDest}");
                $this->deleteJob($job->id);
            } else {
                $this->error("  ❌ Échec d'envoi OTP");
                $this->handleFailedJob($job);
            }
        } else {
            // Pour d'autres types de jobs
            $this->warn("  ⚠️  Type de job non géré: " . get_class($command));
            $this->deleteJob($job->id);
        }
    }

    protected function deleteJob($jobId)
    {
        DB::connection('mongodb')
            ->table('jobs')
            ->where('id', $jobId)
            ->delete();
    }

    protected function handleFailedJob($job)
    {
        if ($job->attempts >= 3) {
            // Déplacer vers failed_jobs
            DB::connection('mongodb')
                ->table('failed_jobs')
                ->insert([
                    'id' => (string) Str::uuid(),
                    'connection' => 'database',
                    'queue' => $job->queue,
                    'payload' => $job->payload,
                    'exception' => 'Max attempts reached',
                    'failed_at' => now()
                ]);

            $this->deleteJob($job->id);
            $this->error("  ❌ Job échoué après 3 tentatives");
        } else {
            // Réinitialiser pour retry
            DB::connection('mongodb')
                ->table('jobs')
                ->where('id', $job->id)
                ->update([
                    'reserved_at' => null,
                    'available_at' => time() + 60
                ]);

            $this->warn("  ⏱️  Retry dans 60 secondes");
        }
    }
}
