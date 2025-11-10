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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id'); // Temporairement sans primary
            $table->uuid('wallet_id'); // Temporairement sans foreign key
            $table->enum('type', ['transfer', 'payment', 'deposit', 'withdrawal']);
            $table->decimal('amount', 15, 2);
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->string('reference'); // Temporairement sans unique
            $table->json('meta')->nullable(); // infos supplémentaires (destinataire, marchand, etc.)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
