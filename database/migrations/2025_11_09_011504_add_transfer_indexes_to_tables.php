<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Temporairement commenté à cause des limitations d'espace disque MongoDB
        // TODO: Réactiver ces index quand l'espace disque sera suffisant ou avec une autre base de données

        /*
        // Ajouter un index sur le champ numero de la table users pour optimiser les recherches
        Schema::table('users', function (Blueprint $table) {
            $table->index('numero');
        });

        // Ajouter des index sur la table transactions pour optimiser les requêtes de transfert
        Schema::table('transactions', function (Blueprint $table) {
            $table->index('reference');
            $table->index(['wallet_id', 'type']);
            $table->index(['type', 'created_at']);
        });

        // Ajouter un index sur user_id de la table wallets
        Schema::table('wallets', function (Blueprint $table) {
            $table->index('user_id');
        });
        */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['numero']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['reference']);
            $table->dropIndex(['wallet_id', 'type']);
            $table->dropIndex(['type', 'created_at']);
        });

        Schema::table('wallets', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });
    }
};
