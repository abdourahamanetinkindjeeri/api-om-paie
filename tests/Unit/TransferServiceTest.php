<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Wallet;
use App\Services\TransferService;
use App\Repositories\TransferRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Exception;

class TransferServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TransferService $transferService;
    protected User $sender;
    protected User $receiver;
    protected Wallet $senderWallet;
    protected Wallet $receiverWallet;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer le service de transfert
        $transferRepository = new TransferRepository(new \App\Models\Transaction());
        $this->transferService = new TransferService($transferRepository);

        // Créer des utilisateurs de test
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

        // Créer des wallets
        $this->senderWallet = Wallet::factory()->create([
            'user_id' => $this->sender->id,
            'balance' => 10000
        ]);

        $this->receiverWallet = Wallet::factory()->create([
            'user_id' => $this->receiver->id,
            'balance' => 5000
        ]);
    }

    /** @test */
    public function it_can_check_if_user_exists()
    {
        $this->assertTrue($this->transferService->checkUserExists($this->sender->numero));
        $this->assertFalse($this->transferService->checkUserExists('999999999'));
    }

    /** @test */
    public function it_can_get_user_balance()
    {
        $balance = $this->transferService->getUserBalance($this->sender->numero);
        $this->assertEquals(10000, $balance);

        $balance = $this->transferService->getUserBalance('999999999');
        $this->assertNull($balance);
    }

    /** @test */
    public function it_can_transfer_money_successfully()
    {
        $result = $this->transferService->transfer(
            $this->sender->numero,
            $this->receiver->numero,
            1000
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(1000, $result['amount']);
        $this->assertEquals(9000, $result['sender']['new_balance']);
        $this->assertEquals(6000, $result['receiver']['new_balance']);
        $this->assertNotEmpty($result['reference']);
    }

    /** @test */
    public function it_throws_exception_for_insufficient_balance()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Solde insuffisant');

        $this->transferService->transfer(
            $this->sender->numero,
            $this->receiver->numero,
            15000 // Plus que le solde disponible
        );
    }

    /** @test */
    public function it_throws_exception_for_invalid_amount()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Le montant doit être supérieur à 0');

        $this->transferService->transfer(
            $this->sender->numero,
            $this->receiver->numero,
            -100
        );
    }

    /** @test */
    public function it_throws_exception_for_non_existent_sender()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Utilisateur expéditeur non trouvé');

        $this->transferService->transfer(
            '999999999',
            $this->receiver->numero,
            1000
        );
    }

    /** @test */
    public function it_throws_exception_for_non_existent_receiver()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Utilisateur destinataire non trouvé');

        $this->transferService->transfer(
            $this->sender->numero,
            '999999999',
            1000
        );
    }

    /** @test */
    public function it_throws_exception_for_self_transfer()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Impossible de faire un transfert vers soi-même');

        $this->transferService->transfer(
            $this->sender->numero,
            $this->sender->numero,
            1000
        );
    }
}
