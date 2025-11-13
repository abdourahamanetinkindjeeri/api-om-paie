<?php

namespace App\Services;

use App\Repositories\WalletRepository;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Gestionnaire spécialisé pour les opérations sur les soldes des wallets
 */
class BalanceManager
{
    protected WalletRepository $walletRepository;

    public function __construct(WalletRepository $walletRepository)
    {
        $this->walletRepository = $walletRepository;
    }

    /**
     * Vérifie si un wallet a suffisamment de solde
     *
     * @param string $walletId
     * @param float $amount
     * @return bool
     */
    public function hasSufficientBalance(string $walletId, float $amount): bool
    {
        $wallet = $this->walletRepository->find($walletId);
        return $wallet && $wallet->balance >= $amount;
    }

    /**
     * Vérifie si un wallet peut recevoir un montant (limites)
     *
     * @param string $walletId
     * @param float $amount
     * @param float $maxBalance
     * @return bool
     */
    public function canReceiveAmount(string $walletId, float $amount, float $maxBalance = 2000000): bool
    {
        $wallet = $this->walletRepository->find($walletId);
        return $wallet && ($wallet->balance + $amount) <= $maxBalance;
    }

    /**
     * Met à jour le solde d'un wallet
     *
     * @param string $walletId
     * @param float $newBalance
     * @param mixed $session Session MongoDB optionnelle pour les transactions
     * @return bool
     */
    public function updateBalance(string $walletId, float $newBalance, $session = null): bool
    {
        try {
            $this->walletRepository->updateBalance($walletId, $newBalance, $session);

            Log::info('Solde wallet mis à jour', [
                'wallet_id' => $walletId,
                'new_balance' => $newBalance,
                'in_transaction' => $session !== null
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Erreur lors de la mise à jour du solde', [
                'wallet_id' => $walletId,
                'new_balance' => $newBalance,
                'error' => $e->getMessage(),
                'in_transaction' => $session !== null
            ]);

            return false;
        }
    }

    /**
     * Crédite un montant sur un wallet
     *
     * @param string $walletId
     * @param float $amount
     * @param mixed $session Session MongoDB optionnelle pour les transactions
     * @return bool
     */
    public function credit(string $walletId, float $amount, $session = null): bool
    {
        $wallet = $this->walletRepository->find($walletId);
        if (!$wallet) {
            return false;
        }

        $newBalance = $wallet->balance + $amount;
        return $this->updateBalance($walletId, $newBalance, $session);
    }

    /**
     * Débite un montant d'un wallet
     *
     * @param string $walletId
     * @param float $amount
     * @param mixed $session Session MongoDB optionnelle pour les transactions
     * @return bool
     */
    public function debit(string $walletId, float $amount, $session = null): bool
    {
        $wallet = $this->walletRepository->find($walletId);
        if (!$wallet) {
            return false;
        }

        $newBalance = $wallet->balance - $amount;
        return $this->updateBalance($walletId, $newBalance, $session);
    }

    /**
     * Effectue un transfert entre deux wallets
     *
     * @param string $fromWalletId
     * @param string $toWalletId
     * @param float $amount
     * @return bool
     */
    public function transfer(string $fromWalletId, string $toWalletId, float $amount): bool
    {
        // Vérifier les soldes avant l'opération
        if (!$this->hasSufficientBalance($fromWalletId, $amount)) {
            return false;
        }

        // Pour les transferts, on ne vérifie pas la limite du destinataire
        // car c'est généralement le même type d'utilisateur

        try {
            // Débiter l'expéditeur
            if (!$this->debit($fromWalletId, $amount)) {
                return false;
            }

            // Créditer le destinataire
            if (!$this->credit($toWalletId, $amount)) {
                // Rollback: remettre l'argent à l'expéditeur
                $this->credit($fromWalletId, $amount);
                return false;
            }

            Log::info('Transfert entre wallets effectué', [
                'from_wallet' => $fromWalletId,
                'to_wallet' => $toWalletId,
                'amount' => $amount
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Erreur lors du transfert entre wallets', [
                'from_wallet' => $fromWalletId,
                'to_wallet' => $toWalletId,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Obtient le solde actuel d'un wallet
     *
     * @param string $walletId
     * @return float|null
     */
    public function getBalance(string $walletId): ?float
    {
        $wallet = $this->walletRepository->find($walletId);
        return $wallet ? $wallet->balance : null;
    }

    /**
     * Valide une opération de paiement
     *
     * @param string $userWalletId
     * @param string $merchantWalletId
     * @param float $amount
     * @param float $merchantMaxBalance
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validatePayment(string $userWalletId, string $merchantWalletId, float $amount, float $merchantMaxBalance = 2000000): array
    {
        $errors = [];

        if ($amount <= 0) {
            $errors[] = 'Le montant doit être supérieur à 0';
        }

        if (!$this->hasSufficientBalance($userWalletId, $amount)) {
            $errors[] = 'Solde insuffisant';
        }

        if (!$this->canReceiveAmount($merchantWalletId, $amount, $merchantMaxBalance)) {
            $errors[] = 'Le marchand dépasserait la limite maximum autorisée';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Valide une opération de transfert
     *
     * @param string $senderWalletId
     * @param string $receiverWalletId
     * @param float $amount
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateTransfer(string $senderWalletId, string $receiverWalletId, float $amount): array
    {
        $errors = [];

        if ($amount <= 0) {
            $errors[] = 'Le montant doit être supérieur à 0';
        }

        if ($senderWalletId === $receiverWalletId) {
            $errors[] = 'Impossible de faire un transfert vers soi-même';
        }

        if (!$this->hasSufficientBalance($senderWalletId, $amount)) {
            $errors[] = 'Solde insuffisant';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
