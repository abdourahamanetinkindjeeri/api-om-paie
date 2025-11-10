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
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id'); // Supprimé primary() et index() pour éviter les contraintes d'espace
            $table->string('nom');
            $table->string('prenom');
            $table->enum('type_piece', ['cni', 'passport', 'permis']);
            $table->string('numero'); // Supprimé unique() pour éviter les index
            $table->string('adresse');
            $table->string('code'); // PIN hashé
            $table->string('telephone'); // Supprimé unique() pour éviter les index
            $table->string('email')->nullable(); // Supprimé unique() pour éviter les index
            $table->timestamp('email_verified_at')->nullable();
            $table->integer('login_attempts')->default(0);
            $table->timestamp('blocked_until')->nullable();
            $table->timestamp('last_login_attempt')->nullable();
            $table->rememberToken();
            $table->timestamps();

            // Supprimé tous les index pour économiser l'espace disque
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
