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
            $table->uuid('id')->primary();
            $table->string('nom');
            $table->string('prenom');
            $table->enum('type_piece', ['cni', 'passport', 'permis']);
            $table->string('numero')->unique(); // numéro de pièce
            $table->string('adresse');
            $table->string('code'); // PIN hashé
            $table->string('telephone')->unique();
            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->integer('login_attempts')->default(0);
            $table->timestamp('blocked_until')->nullable();
            $table->timestamp('last_login_attempt')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->index('telephone');
            $table->index('email');
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
