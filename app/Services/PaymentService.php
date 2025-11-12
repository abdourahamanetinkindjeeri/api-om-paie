<?php

namespace App\Services;

use App\Repositories\PaymentRepository;
use App\Services\Contracts\PaymentServiceInterface;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Facades\Notification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Exception;

class PaymentService extends BaseService implements PaymentServiceInterface
{
    protected PaymentRepository $paymentRepository;

    public function __construct(PaymentRepository $repository)
    {
        parent::__construct($repository);
        $this->paymentRepository = $repository;
    }

    /**
     * Effectuer un paiement vers un marchand avec transactions ACID et protections
     */
    public function payMerchant(object $user, string $merchantCode, float $amount, array $metadata = []): array
    {
        if ($amount <= 0) {
            throw new Exception('Le montant doit être supérieur à 0');
        }

        // Vérifier la référence externe pour déduplication
        $externalRef = $metadata['reference_externe'] ?? null;
        if ($externalRef) {
            $existingTransaction = $this->paymentRepository->findTransactionByExternalReference($externalRef, $user->id);
            if ($existingTransaction) {
                throw new Exception('Cette référence externe a déjà été utilisée pour un paiement');
            }
        }

        // Protection contre le double-clic avec cache
        $cacheKey = "payment_processing_{$user->id}_{$merchantCode}_{$amount}";
        if (Cache::has($cacheKey)) {
            throw new Exception('Paiement en cours de traitement, veuillez patienter');
        }
        Cache::put($cacheKey, true, 30); // 30 secondes de protection

        try {
            // Récupération marchand et wallets
            $merchant = $this->paymentRepository->findMerchantByCode($merchantCode);
            if (!$merchant) {
                throw new Exception('Code marchand invalide ou marchand inactif');
            }

            $userWallet = $user->wallet;
            $merchantWallet = $this->paymentRepository->getMerchantWallet($merchant->id);

            if (!$userWallet || !$merchantWallet) {
                throw new Exception('Wallet utilisateur ou marchand introuvable');
            }

            // Vérification solde
            if ($userWallet->balance < $amount) {
                throw new Exception('Solde insuffisant');
            }

            if (($merchantWallet->balance + $amount) > 2000000) {
                throw new Exception('Le marchand dépasserait la limite maximum de 2,000,000 FCFA');
            }

            // Générer références uniques
            $paymentRef = 'PAY_' . Str::uuid();
            $debitRef = 'DEB_' . Str::uuid();
            $creditRef = 'CRD_' . Str::uuid();

            $defaultMeta = [
                'user_phone' => $user->telephone,
                'merchant_code' => $merchantCode,
                'merchant_name' => $merchant->name,
                'payment_reference' => $paymentRef
            ];

            if ($externalRef) {
                $defaultMeta['reference_externe'] = $externalRef;
            }

            $fullMeta = array_merge($defaultMeta, $metadata);

            // Utiliser une transaction MongoDB pour assurer l'ACID
            return DB::transaction(function () use (
                $userWallet, $merchantWallet, $amount, $debitRef, $creditRef, $fullMeta,
                $paymentRef, $user, $merchant
            ) {
                // Créer transaction débit
                $debitTransaction = $this->paymentRepository->createPaymentTransaction([
                    'wallet_id' => $userWallet->id,
                    'type' => 'payment',
                    'amount' => -$amount,
                    'status' => 'success',
                    'reference' => $debitRef,
                    'meta' => array_merge($fullMeta, ['transaction_type' => 'debit', 'direction' => 'out'])
                ]);

                // Créer transaction crédit
                $creditTransaction = $this->paymentRepository->createPaymentTransaction([
                    'wallet_id' => $merchantWallet->id,
                    'type' => 'payment',
                    'amount' => $amount,
                    'status' => 'success',
                    'reference' => $creditRef,
                    'meta' => array_merge($fullMeta, ['transaction_type' => 'credit', 'direction' => 'in'])
                ]);

                // Mise à jour balances
                $newUserBalance = $userWallet->balance - $amount;
                $newMerchantBalance = $merchantWallet->balance + $amount;

                $this->paymentRepository->updateWalletBalance($userWallet->id, $newUserBalance);
                $this->paymentRepository->updateWalletBalance($merchantWallet->id, $newMerchantBalance);

                // Notification SMS à l'utilisateur (nouveau solde)
                try {
                    if (!empty($user->telephone)) {
                        $fmtAmount = number_format($amount, 0, ',', ' ');
                        $fmtBalance = number_format($newUserBalance, 0, ',', ' ');
                        $message = "OM-Paie: Paiement de {$fmtAmount} FCFA chez {$merchant->name}. Nouveau solde: {$fmtBalance} FCFA. Ref: {$paymentRef}";
                        Notification::send($user->telephone, $message);

                        Log::info('Notification paiement envoyée (SMS)', [
                            'user_id' => $user->id ?? null,
                            'telephone' => $user->telephone,
                            'payment_ref' => $paymentRef,
                            'amount' => $amount,
                            'new_balance' => $newUserBalance
                        ]);
                    } else {
                        Log::warning('Téléphone utilisateur manquant - notification paiement non envoyée', [
                            'user_id' => $user->id ?? null,
                            'payment_ref' => $paymentRef
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::error('Erreur envoi SMS notification paiement', [
                        'user_id' => $user->id ?? null,
                        'telephone' => $user->telephone ?? null,
                        'error' => $e->getMessage()
                    ]);
                }

                return [
                    'success' => true,
                    'reference' => $paymentRef,
                    'user' => [
                        'phone' => $user->telephone,
                        'name' => $user->nom . ' ' . $user->prenom,
                        'new_balance' => $newUserBalance
                    ],
                    'merchant' => [
                        'code' => $merchant->code,
                        'name' => $merchant->name,
                        'new_balance' => $newMerchantBalance
                    ],
                    'amount' => $amount,
                    'debit_transaction' => $debitTransaction,
                    'credit_transaction' => $creditTransaction,
                    'metadata' => $fullMeta
                ];
            });
        } catch (Exception $e) {
            // Supprimer le cache en cas d'erreur
            Cache::forget($cacheKey);
            throw new Exception('Erreur lors du traitement du paiement : ' . $e->getMessage());
        } finally {
            // Supprimer le cache après succès
            Cache::forget($cacheKey);
        }
    }

    public function checkMerchantExists(string $merchantCode): bool
    {
        return $this->paymentRepository->findMerchantByCode($merchantCode) !== null;
    }

    public function getMerchantInfo(string $merchantCode): ?array
    {
        $merchant = $this->paymentRepository->findMerchantByCode($merchantCode);
        if (!$merchant) return null;

        $wallet = $this->paymentRepository->getMerchantWallet($merchant->id);

        return [
            'code' => $merchant->code,
            'name' => $merchant->name,
            'telephone' => $merchant->telephone,
            'email' => $merchant->email,
            'status' => $merchant->status,
            'balance' => $wallet ? $wallet->balance : 0
        ];
    }

    public function getUserPaymentHistory(string $userPhone, int $page = 1, int $limit = 10): LengthAwarePaginator
    {
        $user = $this->paymentRepository->findUserByPhone($userPhone);
        if (!$user) throw new Exception('Utilisateur non trouvé');

        return $this->paymentRepository->getUserPaymentHistory($user->id, $page, $limit);
    }

    public function getMerchantPaymentHistory(string $merchantCode, int $page = 1, int $limit = 10): LengthAwarePaginator
    {
        $merchant = $this->paymentRepository->findMerchantByCode($merchantCode);
        if (!$merchant) throw new Exception('Marchand non trouvé');

        return $this->paymentRepository->getMerchantPaymentHistory($merchant->id, $page, $limit);
    }
}
