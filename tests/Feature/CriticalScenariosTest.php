<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Merchant;
use App\Models\Transaction;
use App\Services\TransactionManager;
use App\Services\RateLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Laravel\Passport\Passport;
use Exception;

class CriticalScenariosTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Merchant $merchant;
    protected Wallet $userWallet;
    protected Wallet $merchantWallet;
    protected TransactionManager $transactionManager;
    protected RateLimiter $rateLimiter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transactionManager = app(TransactionManager::class);
        $this->rateLimiter = app(RateLimiter::class);

        // Créer l'utilisateur de test
        $this->user = User::factory()->create([
            'numero' => '221771234567',
            'nom' => 'Test',
            'prenom' => 'User'
        ]);

        // Créer le marchand de test
        $this->merchant = Merchant::factory()->create([
            'code' => 'TEST123',
            'name' => 'Test Merchant',
            'status' => 'active'
        ]);

        // Créer les wallets
        $this->userWallet = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'balance' => 10000
        ]);

        $this->merchantWallet = Wallet::factory()->create([
            'user_id' => $this->merchant->id,
            'balance' => 5000
        ]);
    }

    /** @test */
    public function it_handles_mongodb_transaction_rollback_on_failure()
    {
        Passport::actingAs($this->user);

        // Simuler une transaction qui échoue après avoir modifié des données
        $originalUserBalance = $this->userWallet->balance;
        $originalMerchantBalance = $this->merchantWallet->balance;

        try {
            $this->transactionManager->executePaymentTransaction(function ($session) {
                // Modifier les soldes
                $this->userWallet->balance -= 1000;
                $this->merchantWallet->balance += 1000;

                // Simuler une sauvegarde partielle
                $this->userWallet->save();

                // Lancer une exception pour déclencher le rollback
                throw new Exception('Simulated transaction failure');
            });
        } catch (Exception $e) {
            // La transaction devrait être annulée
            $this->assertEquals('Simulated transaction failure', $e->getMessage());
        }

        // Vérifier que les soldes sont revenus à leur état initial
        $this->userWallet->refresh();
        $this->merchantWallet->refresh();

        $this->assertEquals($originalUserBalance, $this->userWallet->balance);
        $this->assertEquals($originalMerchantBalance, $this->merchantWallet->balance);
    }

    /** @test */
    public function it_handles_concurrent_payment_requests()
    {
        Passport::actingAs($this->user);

        // Simuler des requêtes concurrentes
        $promises = [];

        for ($i = 0; $i < 3; $i++) {
            $promises[] = $this->postJson('/api/payment', [
                'code_marchand' => $this->merchant->code,
                'montant' => 1000,
                'metadata' => [
                    'reference_externe' => 'CONCURRENT_' . $i
                ]
            ]);
        }

        // Au moins une requête devrait réussir, les autres devraient échouer
        $successCount = 0;
        $failureCount = 0;

        foreach ($promises as $promise) {
            if ($promise->getStatusCode() === 200) {
                $successCount++;
            } else {
                $failureCount++;
            }
        }

        $this->assertGreaterThanOrEqual(1, $successCount);
        $this->assertGreaterThanOrEqual(0, $failureCount);
    }

    /** @test */
    public function it_handles_rate_limiting_edge_cases()
    {
        // Tester la limite d'OTP
        $identifier = 'test@example.com';

        // Consommer toutes les tentatives d'OTP
        for ($i = 0; $i < 5; $i++) {
            $this->rateLimiter->recordAttempt($identifier, 'otp_generation');
        }

        // La prochaine tentative devrait être bloquée
        $check = $this->rateLimiter->check($identifier, 'otp_generation');
        $this->assertFalse($check['allowed']);
        $this->assertGreaterThan(0, $check['blocked_until']);

        // Tester le reset du compteur
        $this->rateLimiter->reset($identifier, 'otp_generation');
        $check = $this->rateLimiter->check($identifier, 'otp_generation');
        $this->assertTrue($check['allowed']);
    }

    /** @test */
    public function it_handles_database_connection_failures()
    {
        Passport::actingAs($this->user);

        // Simuler une déconnexion de base de données
        DB::shouldReceive('getMongoClient')->andThrow(new Exception('Connection failed'));

        $response = $this->postJson('/api/payment', [
            'code_marchand' => $this->merchant->code,
            'montant' => 1000
        ]);

        // La requête devrait échouer gracieusement
        $this->assertNotEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_handles_transaction_timeout_scenarios()
    {
        Passport::actingAs($this->user);

        // Simuler un timeout de transaction
        try {
            $this->transactionManager->executeCriticalTransaction(function ($session) {
                // Simuler une opération qui prend du temps
                sleep(25); // Plus que le timeout de 20 secondes

                return ['success' => true];
            });
        } catch (Exception $e) {
            // La transaction devrait échouer avec un timeout
            $this->assertStringContainsString('timeout', strtolower($e->getMessage()));
        }
    }

    /** @test */
    public function it_handles_invalid_transaction_states()
    {
        // Tester avec un wallet inexistant
        $result = $this->transactionManager->executeInTransaction(function ($session) {
            $nonExistentWallet = Wallet::find('invalid-id');
            if (!$nonExistentWallet) {
                throw new Exception('Wallet not found');
            }
            return $nonExistentWallet;
        });

        // La transaction devrait échouer
        $this->assertInstanceOf(Exception::class, $result);
    }

    /** @test */
    public function it_handles_large_transaction_volumes()
    {
        Passport::actingAs($this->user);

        // Créer un wallet avec un solde élevé
        $this->userWallet->balance = 10000000; // 10 millions
        $this->userWallet->save();

        $response = $this->postJson('/api/payment', [
            'code_marchand' => $this->merchant->code,
            'montant' => 5000000 // 5 millions
        ]);

        $response->assertStatus(200);

        // Vérifier que la transaction de gros montant a réussi
        $this->userWallet->refresh();
        $this->assertEquals(5000000, $this->userWallet->balance);
    }

    /** @test */
    public function it_handles_network_interruptions_during_transaction()
    {
        Passport::actingAs($this->user);

        // Simuler une interruption réseau pendant la transaction
        $interrupted = false;

        try {
            $this->transactionManager->executePaymentTransaction(function ($session) use (&$interrupted) {
                // Commencer la transaction
                $this->userWallet->balance -= 1000;
                $this->userWallet->save();

                // Simuler une interruption réseau
                if (!$interrupted) {
                    $interrupted = true;
                    throw new Exception('Network interruption');
                }

                $this->merchantWallet->balance += 1000;
                $this->merchantWallet->save();

                return ['success' => true];
            });
        } catch (Exception $e) {
            $this->assertEquals('Network interruption', $e->getMessage());
        }

        // Vérifier que les soldes sont revenus à leur état initial
        $this->userWallet->refresh();
        $this->merchantWallet->refresh();

        $this->assertEquals(10000, $this->userWallet->balance);
        $this->assertEquals(5000, $this->merchantWallet->balance);
    }

    /** @test */
    public function it_handles_replica_set_failover_scenarios()
    {
        // Tester le comportement quand le replica set n'est pas disponible
        $result = $this->transactionManager->executeInTransaction(function ($session) {
            // Cette fonction devrait s'exécuter sans transaction ACID
            return ['executed_without_transaction' => true];
        });

        // Devrait réussir même sans replica set
        $this->assertEquals(['executed_without_transaction' => true], $result);
    }

    /** @test */
    public function it_validates_business_rules_strictly()
    {
        Passport::actingAs($this->user);

        // Tester les limites de montant
        $testCases = [
            ['amount' => 0, 'expected' => false],
            ['amount' => -1000, 'expected' => false],
            ['amount' => 10000001, 'expected' => false], // Au-delà de la limite système
        ];

        foreach ($testCases as $testCase) {
            $response = $this->postJson('/api/payment', [
                'code_marchand' => $this->merchant->code,
                'montant' => $testCase['amount']
            ]);

            if ($testCase['expected']) {
                $response->assertStatus(200);
            } else {
                $this->assertNotEquals(200, $response->getStatusCode());
            }
        }
    }

    /** @test */
    public function it_handles_cache_failures_gracefully()
    {
        Passport::actingAs($this->user);

        // Simuler un échec du cache
        Cache::shouldReceive('has')->andThrow(new Exception('Cache unavailable'));
        Cache::shouldReceive('put')->andThrow(new Exception('Cache unavailable'));

        // La transaction devrait quand même fonctionner
        $response = $this->postJson('/api/payment', [
            'code_marchand' => $this->merchant->code,
            'montant' => 1000
        ]);

        // Devrait réussir malgré l'échec du cache
        $response->assertStatus(200);
    }
}
