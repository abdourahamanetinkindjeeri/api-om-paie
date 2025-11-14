<?php

namespace App\Console\Commands;

use App\Models\Wallet;
use Illuminate\Console\Command;

class UpdateWalletsIsMain extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wallets:update-is-main';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Met à jour les wallets existants pour définir le premier comme principal';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Mise à jour des wallets existants...');

        // Récupérer tous les utilisateurs qui ont des wallets
        $usersWithWallets = Wallet::distinct('user_id')->pluck('user_id');

        $totalUpdated = 0;

        foreach ($usersWithWallets as $userId) {
            // Récupérer les wallets de l'utilisateur triés par date de création
            $wallets = Wallet::where('user_id', $userId)
                ->orderBy('created_at', 'asc')
                ->get();

            $first = true;
            foreach ($wallets as $wallet) {
                $wallet->is_main = $first;
                $wallet->save();
                $first = false;
                $totalUpdated++;
            }
        }

        // Forcer la mise à jour de tous les wallets sans is_main défini
        $walletsWithoutIsMain = Wallet::whereNull('is_main')->orWhere('is_main', 'exists', false)->get();
        foreach ($walletsWithoutIsMain as $wallet) {
            // Trouver si c'est le premier wallet de l'utilisateur
            $userWallets = Wallet::where('user_id', $wallet->user_id)
                ->orderBy('created_at', 'asc')
                ->pluck('id')
                ->toArray();

            $isFirst = $userWallets[0] === $wallet->id;
            $wallet->is_main = $isFirst;
            $wallet->save();
            $totalUpdated++;
        }

        $this->info("Mise à jour terminée. {$totalUpdated} wallets mis à jour.");
    }
}
