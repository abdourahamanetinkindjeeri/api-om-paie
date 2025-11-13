<?php

namespace Tests\Unit;

use App\Services\TransactionManager;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Exception;

class TransactionManagerTest extends TestCase
{
    use RefreshDatabase;

    protected TransactionManager $transactionManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transactionManager = app(TransactionManager::class);
    }

    /** @test */
    public function it_can_execute_transaction_with_replica_set()
    {
        // Simuler une transaction réussie
        $result = $this->transactionManager->executeInTransaction(function () {
            // Créer une transaction de test
            $transaction = Transaction::create([
                'wallet_id' => 'test-wallet-id',
                'type' => 'test',
                'amount' => 1000,
                'status' => 'success',
                'reference' => 'TEST_' . uniqid(),
                'meta' => ['test' => true]
            ]);

            return $transaction;
        });

        $this->assertInstanceOf(Transaction::class, $result);
        $this->assertEquals(1000, $result->amount);
        $this->assertEquals('test', $result->type);
    }

    /** @test */
    public function it_rolls_back_transaction_on_exception()
    {
        $this->expectException(Exception::class);

        try {
            $this->transactionManager->executeInTransaction(function () {
                // Créer une transaction
                Transaction::create([
                    'wallet_id' => 'test-wallet-id',
                    'type' => 'test',
                    'amount' => 1000,
                    'status' => 'success',
                    'reference' => 'TEST_' . uniqid(),
                    'meta' => ['test' => true]
                ]);

                // Lancer une exception pour déclencher le rollback
                throw new Exception('Test rollback');
            });
        } catch (Exception $e) {
            // Vérifier que la transaction n'a pas été créée
            $transaction = Transaction::where('wallet_id', 'test-wallet-id')->first();
            $this->assertNull($transaction);

            throw $e;
        }
    }

    /** @test */
    public function it_can_execute_payment_transaction()
    {
        $result = $this->transactionManager->executePaymentTransaction(function () {
            return ['payment_processed' => true, 'amount' => 5000];
        });

        $this->assertEquals(['payment_processed' => true, 'amount' => 5000], $result);
    }

    /** @test */
    public function it_can_execute_transfer_transaction()
    {
        $result = $this->transactionManager->executeTransferTransaction(function () {
            return ['transfer_processed' => true, 'amount' => 2500];
        });

        $this->assertEquals(['transfer_processed' => true, 'amount' => 2500], $result);
    }

    /** @test */
    public function it_can_execute_critical_transaction()
    {
        $result = $this->transactionManager->executeCriticalTransaction(function () {
            return ['critical_operation' => true, 'data' => 'sensitive'];
        });

        $this->assertEquals(['critical_operation' => true, 'data' => 'sensitive'], $result);
    }

    /** @test */
    public function it_handles_nested_transactions()
    {
        $result = $this->transactionManager->executeInTransaction(function () {
            // Transaction interne
            $innerResult = $this->transactionManager->executeInTransaction(function () {
                return 'inner_transaction_result';
            });

            return [
                'outer' => true,
                'inner' => $innerResult
            ];
        });

        $this->assertEquals([
            'outer' => true,
            'inner' => 'inner_transaction_result'
        ], $result);
    }
}
