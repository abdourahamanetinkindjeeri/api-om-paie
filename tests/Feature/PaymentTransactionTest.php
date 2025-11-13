<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Laravel\Passport\Passport;

class PaymentTransactionTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Merchant $merchant;
    protected Wallet $userWallet;
    protected Wallet $merchantWallet;

    protected function setUp(): void
    {
        parent::setUp();

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
            'balance' => 10000 // 10,000 FCFA
        ]);

        $this->merchantWallet = Wallet::factory()->create([
            'user_id' => $this->merchant->id,
            'balance' => 5000 // 5,000 FCFA
        ]);
    }

    /** @test */
    public function it_processes_payment_with_mongodb_transaction()
    {
        Passport::actingAs($this->user);

        $response = $this->postJson('/api/payment', [
            'code_marchand' => $this->merchant->code,
            'montant' => 2500,
            'metadata' => [
                'reference_externe' => 'TEST_REF_123'
            ]
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'user' => [
                    'phone' => $this->user->numero,
                    'new_balance' => 7500 // 10,000 - 2,500
                ],
                'merchant' => [
                    'code' => $this->merchant->code,
                    'new_balance' => 7500 // 5,000 + 2,500
                ],
                'amount' => 2500
            ]);

        // Vérifier que les soldes ont été mis à jour
        $this->userWallet->refresh();
        $this->merchantWallet->refresh();

        $this->assertEquals(7500, $this->userWallet->balance);
        $this->assertEquals(7500, $this->merchantWallet->balance);
    }

    /** @test */
    public function it_rolls_back_payment_on_failure()
    {
        Passport::actingAs($this->user);

        // Tenter un paiement qui dépasserait la limite du marchand
        $response = $this->postJson('/api/payment', [
            'code_marchand' => $this->merchant->code,
            'montant' => 2500000, // Plus que la limite de 2,000,000 FCFA
        ]);

        $response->assertStatus(400);

        // Vérifier que les soldes n'ont pas changé
        $this->userWallet->refresh();
        $this->merchantWallet->refresh();

        $this->assertEquals(10000, $this->userWallet->balance);
        $this->assertEquals(5000, $this->merchantWallet->balance);
    }

    /** @test */
    public function it_handles_duplicate_external_reference()
    {
        Passport::actingAs($this->user);

        $externalRef = 'DUPLICATE_REF_123';

        // Premier paiement réussi
        $response1 = $this->postJson('/api/payment', [
            'code_marchand' => $this->merchant->code,
            'montant' => 1000,
            'metadata' => [
                'reference_externe' => $externalRef
            ]
        ]);

        $response1->assertStatus(200);

        // Deuxième paiement avec la même référence externe - devrait échouer
        $response2 = $this->postJson('/api/payment', [
            'code_marchand' => $this->merchant->code,
            'montant' => 1000,
            'metadata' => [
                'reference_externe' => $externalRef
            ]
        ]);

        $response2->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Cette référence externe a déjà été utilisée pour un paiement'
            ]);
    }

    /** @test */
    public function it_prevents_double_payment_with_cache_lock()
    {
        Passport::actingAs($this->user);

        // Simuler un paiement en cours avec le cache
        $cacheKey = "payment_processing_{$this->user->id}_{$this->merchant->code}_2500";
        \Illuminate\Support\Facades\Cache::put($cacheKey, true, 30);

        $response = $this->postJson('/api/payment', [
            'code_marchand' => $this->merchant->code,
            'montant' => 2500
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Paiement en cours de traitement, veuillez patienter'
            ]);

        // Nettoyer le cache
        \Illuminate\Support\Facades\Cache::forget($cacheKey);
    }

    /** @test */
    public function it_validates_payment_amount()
    {
        Passport::actingAs($this->user);

        $response = $this->postJson('/api/payment', [
            'code_marchand' => $this->merchant->code,
            'montant' => -1000 // Montant négatif
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Le montant doit être supérieur à 0'
            ]);
    }

    /** @test */
    public function it_checks_sufficient_balance()
    {
        Passport::actingAs($this->user);

        $response = $this->postJson('/api/payment', [
            'code_marchand' => $this->merchant->code,
            'montant' => 15000 // Plus que le solde disponible
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Solde insuffisant'
            ]);
    }

    /** @test */
    public function it_validates_merchant_code()
    {
        Passport::actingAs($this->user);

        $response = $this->postJson('/api/payment', [
            'code_marchand' => 'INVALID_CODE',
            'montant' => 1000
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Code marchand invalide ou marchand inactif'
            ]);
    }

    /** @test */
    public function it_creates_transaction_records()
    {
        Passport::actingAs($this->user);

        $response = $this->postJson('/api/payment', [
            'code_marchand' => $this->merchant->code,
            'montant' => 1000
        ]);

        $response->assertStatus(200);

        // Vérifier que les transactions ont été créées
        $debitTransaction = \App\Models\Transaction::where('wallet_id', $this->userWallet->id)
            ->where('amount', -1000)
            ->first();

        $creditTransaction = \App\Models\Transaction::where('wallet_id', $this->merchantWallet->id)
            ->where('amount', 1000)
            ->first();

        $this->assertNotNull($debitTransaction);
        $this->assertNotNull($creditTransaction);

        $this->assertEquals('payment', $debitTransaction->type);
        $this->assertEquals('payment', $creditTransaction->type);
        $this->assertEquals('success', $debitTransaction->status);
        $this->assertEquals('success', $creditTransaction->status);
    }
}
