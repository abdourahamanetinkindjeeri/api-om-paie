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
        // Cette migration est pour MongoDB, donc pas besoin de structure de table classique
        // MongoDB créera automatiquement la collection lors du premier insert
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Pour MongoDB, on peut supprimer la collection si nécessaire
        // Schema::connection('mongodb')->drop('otp_codes');
    }
};
