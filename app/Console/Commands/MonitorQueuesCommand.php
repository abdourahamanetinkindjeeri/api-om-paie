<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class MonitorQueuesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:monitor {--watch : Surveillance continue}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Surveiller l\'état des queues et des workers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('watch')) {
            $this->watchQueues();
        } else {
            $this->showQueueStatus();
        }
    }

    /**
     * Afficher l'état actuel des queues
     */
    private function showQueueStatus(): void
    {
        $this->info("📊 État des queues - " . now()->format('Y-m-d H:i:s'));
        $this->newLine();

        // Configuration des queues
        $this->info("🔧 Configuration :");
        $this->line("  - Connection : " . config('queue.default'));
        $this->line("  - Driver     : " . config('queue.connections.' . config('queue.default') . '.driver'));
        $this->newLine();

        // Jobs en attente
        if (config('queue.default') === 'database') {
            $this->showDatabaseQueueStatus();
        }

        // Jobs échoués
        $this->showFailedJobs();

        // Vérifier si le worker tourne
        $this->checkWorkerStatus();
    }

    /**
     * Surveiller les queues en continu
     */
    private function watchQueues(): void
    {
        $this->info("👀 Surveillance des queues en cours... (Ctrl+C pour arrêter)");
        $this->newLine();

        while (true) {
            // Effacer l'écran
            system('clear');

            $this->showQueueStatus();

            sleep(5);
        }
    }

    /**
     * Afficher l'état des queues database
     */
    private function showDatabaseQueueStatus(): void
    {
        try {
            $pending = DB::table('jobs')->count();
            $processing = DB::table('jobs')->whereNotNull('reserved_at')->count();
            $available = $pending - $processing;

            $this->info("📋 Jobs en base de données :");
            $this->line("  - En attente : " . $available);
            $this->line("  - En cours   : " . $processing);
            $this->line("  - Total      : " . $pending);
            $this->newLine();

            // Détail par queue
            $queues = DB::table('jobs')
                ->select('queue', DB::raw('count(*) as total'))
                ->groupBy('queue')
                ->get();

            if ($queues->count() > 0) {
                $this->info("📊 Répartition par queue :");
                foreach ($queues as $queue) {
                    $this->line("  - {$queue->queue} : {$queue->total} jobs");
                }
                $this->newLine();
            }
        } catch (\Exception $e) {
            $this->error("❌ Erreur lors de la lecture des jobs : " . $e->getMessage());
        }
    }

    /**
     * Afficher les jobs échoués
     */
    private function showFailedJobs(): void
    {
        try {
            $failedCount = DB::table('failed_jobs')->count();

            if ($failedCount > 0) {
                $this->warn("⚠️ Jobs échoués : " . $failedCount);

                $recent = DB::table('failed_jobs')
                    ->orderBy('failed_at', 'desc')
                    ->limit(3)
                    ->get(['payload', 'exception', 'failed_at']);

                foreach ($recent as $failed) {
                    $payload = json_decode($failed->payload, true);
                    $jobName = $payload['displayName'] ?? 'Job inconnu';
                    $this->line("  - {$jobName} à {$failed->failed_at}");
                }
                $this->newLine();
            } else {
                $this->info("✅ Aucun job échoué");
            }
        } catch (\Exception $e) {
            $this->line("⚠️ Impossible de vérifier les jobs échoués");
        }
    }

    /**
     * Vérifier si le worker tourne
     */
    private function checkWorkerStatus(): void
    {
        $this->info("🔍 État du worker :");

        // Vérifier les processus
        $processes = shell_exec("pgrep -f 'queue:work' 2>/dev/null") ?: '';
        $processCount = count(array_filter(explode("\n", trim($processes))));

        if ($processCount > 0) {
            $this->info("✅ Worker actif ($processCount processus)");

            // Afficher les PID
            $pids = array_filter(explode("\n", trim($processes)));
            foreach ($pids as $pid) {
                $this->line("  - PID : $pid");
            }
        } else {
            $this->warn("⚠️ Aucun worker détecté");
            $this->line("  Pour démarrer : ./start-queue-worker.sh &");
        }
    }
}
