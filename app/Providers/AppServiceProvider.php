<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Enregistrement du service de transfert
        $this->app->bind(
            \App\Services\Contracts\TransferServiceInterface::class,
            \App\Services\TransferService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configuration des modèles Passport avec UUIDs
        if (class_exists(\Laravel\Passport\Passport::class)) {
            \Laravel\Passport\Passport::useClientModel(\App\Models\Passport\Client::class);
            \Laravel\Passport\Passport::useTokenModel(\App\Models\Passport\Token::class);
            \Laravel\Passport\Passport::useAuthCodeModel(\App\Models\Passport\AuthCode::class);
            \Laravel\Passport\Passport::useRefreshTokenModel(\App\Models\Passport\RefreshToken::class);
            \Laravel\Passport\Passport::usePersonalAccessClientModel(\App\Models\Passport\PersonalAccessClient::class);
        }
    }
}
