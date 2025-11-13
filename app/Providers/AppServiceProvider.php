<?php

namespace App\Providers;

use App\Models\Passport\AuthCode;
use App\Models\Passport\Client;
use App\Models\Passport\PersonalAccessClient;
use App\Models\Passport\RefreshToken;
use App\Models\Passport\Token;
use App\Services\GmailNotificationService;
use App\Services\NotificationManager;
use App\Services\TwilioNotificationService;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Enregistrement des repositories
        $this->app->bind(
            \App\Repositories\Contracts\MerchantRepositoryInterface::class,
            \App\Repositories\MerchantRepository::class
        );

        // Enregistrement du service de transfert
        $this->app->bind(
            \App\Services\Contracts\TransferServiceInterface::class,
            \App\Services\TransferService::class
        );

        // Enregistrement du service de paiement
        $this->app->bind(
            \App\Services\Contracts\PaymentServiceInterface::class,
            \App\Services\PaymentService::class
        );

        // Enregistrement du service OTP
        $this->app->bind(
            \App\Services\Contracts\OtpServiceInterface::class,
            \App\Services\OtpService::class
        );

        // Enregistrement du service d'enregistrement
        $this->app->bind(
            \App\Services\Contracts\RegistrationServiceInterface::class,
            \App\Services\RegistrationService::class
        );

        // Enregistrement du service d'historique
        $this->app->bind(
            \App\Services\Contracts\HistoryServiceInterface::class,
            \App\Services\HistoryService::class
        );

        // Enregistrement du gestionnaire de transactions
        $this->app->singleton(
            \App\Services\TransactionManager::class,
            \App\Services\TransactionManager::class
        );

        // Enregistrement du limiteur de taux
        $this->app->singleton(
            \App\Services\RateLimiter::class,
            \App\Services\RateLimiter::class
        );

        // Enregistrement du gestionnaire de soldes
        $this->app->singleton(
            \App\Services\BalanceManager::class,
            \App\Services\BalanceManager::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configuration des modèles Passport avec UUIDs
        if (class_exists(Passport::class)) {
            Passport::useClientModel(Client::class);
            Passport::useTokenModel(Token::class);
            Passport::useAuthCodeModel(AuthCode::class);
            Passport::useRefreshTokenModel(RefreshToken::class);
            Passport::usePersonalAccessClientModel(PersonalAccessClient::class);
        }

        $this->app->singleton('notification', function ($app) {
            return new NotificationManager([
                new GmailNotificationService(),
                new TwilioNotificationService(),
            ]);
        });
    }
}
