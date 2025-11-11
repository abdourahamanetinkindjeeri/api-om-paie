<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->bigIncrements('id'); // Clé primaire pour MongoDB
            $table->string('queue');
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        // Créer les index seulement si assez d'espace disque (production)
        if (app()->environment('production') || $this->hasEnoughDiskSpace()) {
            Schema::table('jobs', function (Blueprint $table) {
                $table->index('queue');
            });
        }
    }

    /**
     * Vérifier si MongoDB a assez d'espace pour créer des index
     */
    private function hasEnoughDiskSpace(): bool
    {
        try {
            $stats = DB::connection('mongodb')->getMongoDB()->command(['dbStats' => 1])->toArray();
            $freeBytes = $stats[0]['freeStorageSize'] ?? 0;
            return $freeBytes >= 524288000; // 500MB minimum
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
