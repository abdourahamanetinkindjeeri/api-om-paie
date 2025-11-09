<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Laravel\Passport\Passport;

class TransferTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $sender;
    protected User $receiver;
    protected Wallet $senderWallet;
    protected Wallet $receiverWallet;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer les utilisateurs de test
        $this->sender = User::factory()->create([
            'numero' => '221771234567',
            'nom' => 'Jean',
            'prenom' => 'Dupont'
        ]);

        $this->receiver = User::factory()->create([
            'numero' => '221771234568',
            'nom' => 'Marie',
            'prenom' => 'Martin'
        ]);

        // Créer les wallets avec des soldes
        $this->senderWallet = Wallet::factory()->create([
            'user_id' => $this->sender->id,
            'balance' => 10000 // Solde de 10,000
        ]);

        $this->receiverWallet = Wallet::factory()->create([
            'user_id' => $this->receiver->id,
            'balance' => 5000 // Solde de 5,000
        ]);
    }

    /** @test */
    public function it_can_make_a_successful_transfer()
    {
        Passport::actingAs($this->sender);

        $response = $this->postJson('/api/transfer', [
            'numero' => $this->receiver->numero,
            'montant' => 1000
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Transfert effectué avec succès'
            ]);

        // Vérifier que les soldes ont été mis à jour
        $this->senderWallet->refresh();
        $this->receiverWallet->refresh();

        $this->assertEquals(9000, $this->senderWallet->balance);
        $this->assertEquals(6000, $this->receiverWallet->balance);
    }

    /** @test */
    public function it_fails_when_insufficient_balance()
    {
        Passport::actingAs($this->sender);

        $response = $this->postJson('/api/transfer', [
            'numero' => $this->receiver->numero,
            'montant' => 15000 // Plus que le solde disponible
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Solde insuffisant'
            ]);
    }

    /** @test */
    public function it_fails_when_receiver_does_not_exist()
    {
        Passport::actingAs($this->sender);

        $response = $this->postJson('/api/transfer', [
            'numero' => '221771234569', // Numéro inexistant
            'montant' => 1000
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['numero']);
    }

    /** @test */
    public function it_fails_with_invalid_amount()
    {
        Passport::actingAs($this->sender);

        $response = $this->postJson('/api/transfer', [
            'numero' => $this->receiver->numero,
            'montant' => -100 // Montant négatif
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['montant']);
    }

    /** @test */
    public function it_can_check_if_number_exists()
    {
        Passport::actingAs($this->sender);

        $response = $this->postJson('/api/transfer/check-number', [
            'numero' => $this->receiver->numero
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'exists' => true,
                'message' => 'Numéro trouvé'
            ]);
    }

    /** @test */
    public function it_can_get_user_balance()
    {
        Passport::actingAs($this->sender);

        $response = $this->getJson('/api/transfer/balance');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'solde' => 10000,
                'numero' => $this->sender->numero
            ]);
    }

    /** @test */
    public function it_requires_authentication_for_transfer()
    {
        $response = $this->postJson('/api/transfer', [
            'numero' => $this->receiver->numero,
            'montant' => 1000
        ]);

        $response->assertStatus(401);
    }
}
