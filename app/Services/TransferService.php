<?php

namespace App\Services;

use App\Repositories\TransferRepository;
use App\Repositories\WalletRepository;
use App\Services\Contracts\TransferServiceInterface;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Facades\Notification;
use Exception;

class TransferService extends BaseService implements TransferServiceInterface
{
    protected TransferRepository $transferRepository;
    protected WalletRepository $walletRepository;

    public function __construct(TransferRepository $repository, WalletRepository $walletRepository)
    {
        parent::__construct($repository);
        $this->transferRepository = $repository;
        $this->walletRepository = $walletRepository;
    }

    /**
     * Effectuer un transfert entre utilisateurs avec protections ACID
     */
    public function transfer(string $senderNumber, string $receiverNumber, float $amount, array $metadata = []): array
    {
        // Vérifier la référence externe pour déduplication
        $externalRef = $metadata['reference_externe'] ?? null;
        if ($externalRef) {
            $existingTransaction = $this->transferRepository->findTransactionByExternalReference($externalRef, $senderNumber);
            if ($existingTransaction) {
                throw new Exception('Cette référence externe a déjà été utilisée pour un transfert');
            }
        }

        // Protection contre le double-clic avec cache
        $cacheKey = "transfer_processing_{$senderNumber}_{$receiverNumber}_{$amount}";
        if (Cache::has($cacheKey)) {
            throw new Exception('Transfert en cours de traitement, veuillez patienter');
        }
        Cache::put($cacheKey, true, 30); // 30 secondes de protection

        try {
            // Validation du montant
            if ($amount <= 0) {
                throw new Exception('Le montant doit être supérieur à 0');
            }

            // Trouver l'utilisateur expéditeur
            $sender = $this->transferRepository->findUserByNumber($senderNumber);
            if (!$sender) {
                throw new Exception('Utilisateur expéditeur non trouvé');
            }

            // Trouver l'utilisateur destinataire
            $receiver = $this->transferRepository->findUserByNumber($receiverNumber);
            if (!$receiver) {
                throw new Exception('Utilisateur destinataire non trouvé');
            }

            // Vérifier que l'expéditeur et le destinataire sont différents
            if ($sender->id === $receiver->id) {
                throw new Exception('Impossible de faire un transfert vers soi-même');
            }

            // Obtenir les wallets
            $senderWallet = $this->transferRepository->getUserWallet($sender->id);
            $receiverWallet = $this->transferRepository->getUserWallet($receiver->id);

            if (!$senderWallet) {
                throw new Exception('Wallet de l\'expéditeur non trouvé');
            }

            // Créer automatiquement un wallet pour le destinataire s'il n'en a pas
            if (!$receiverWallet) {
                Log::info('Création automatique d\'un wallet pour le destinataire', [
                    'receiver_id' => $receiver->id,
                    'receiver_number' => $receiverNumber
                ]);

                $receiverWallet = $this->walletRepository->createForUser($receiver->id, 'XOF', 0.0);

                Log::info('Wallet créé automatiquement', [
                    'wallet_id' => $receiverWallet->id,
                    'receiver_id' => $receiver->id
                ]);
            }

            // Vérifier le solde suffisant
            if ($senderWallet->balance < $amount) {
                throw new Exception('Solde insuffisant');
            }

            // Générer des références uniques pour chaque transaction
            $transferRef = 'TRF_' . Str::uuid();
            $debitRef = 'DBT_' . Str::uuid();
            $creditRef = 'CRD_' . Str::uuid();

            $defaultMeta = [
                'sender_number' => $senderNumber,
                'receiver_number' => $receiverNumber,
                'transfer_reference' => $transferRef
            ];

            if ($externalRef) {
                $defaultMeta['reference_externe'] = $externalRef;
            }

            $fullMeta = array_merge($defaultMeta, $metadata);

            // Exécuter les opérations de transfert séquentiellement (pas de transaction MongoDB sur instance standalone)
            $debitTransaction = null;
            $creditTransaction = null;
            $senderBalanceUpdated = false;
            $receiverBalanceUpdated = false;
            $newSenderBalance = $senderWallet->balance - $amount;
            $newReceiverBalance = $receiverWallet->balance + $amount;

            try {
                // Créer la transaction de débit (expéditeur)
                $debitTransaction = $this->transferRepository->createTransferTransaction([
                    'wallet_id' => $senderWallet->id,
                    'type' => 'transfer',
                    'amount' => -$amount, // Montant négatif pour le débit
                    'status' => 'success',
                    'reference' => $debitRef,
                    'meta' => array_merge($fullMeta, ['transfer_type' => 'debit'])
                ]);

                // Créer la transaction de crédit (destinataire)
                $creditTransaction = $this->transferRepository->createTransferTransaction([
                    'wallet_id' => $receiverWallet->id,
                    'type' => 'transfer',
                    'amount' => $amount, // Montant positif pour le crédit
                    'status' => 'success',
                    'reference' => $creditRef,
                    'meta' => array_merge($fullMeta, ['transfer_type' => 'credit'])
                ]);

                // Mettre à jour les soldes
                $this->transferRepository->updateWalletBalance($senderWallet->id, $newSenderBalance);
                $senderBalanceUpdated = true;

                $this->transferRepository->updateWalletBalance($receiverWallet->id, $newReceiverBalance);
                $receiverBalanceUpdated = true;
            } catch (Exception $e) {
                // Rollback manuel en cas d'erreur
                if ($receiverBalanceUpdated) {
                    $this->transferRepository->updateWalletBalance($receiverWallet->id, $receiverWallet->balance);
                }
                if ($senderBalanceUpdated) {
                    $this->transferRepository->updateWalletBalance($senderWallet->id, $senderWallet->balance);
                }
                if ($creditTransaction) {
                    // Supprimer la transaction crédit si elle existe
                    $this->transferRepository->delete($creditTransaction->id);
                }
                if ($debitTransaction) {
                    // Supprimer la transaction débit si elle existe
                    $this->transferRepository->delete($debitTransaction->id);
                }
                throw $e;
            }

            // Notifications SMS (nouveau solde)
            try {
                if (!empty($sender->telephone)) {
                    $fmtAmount = number_format($amount, 0, ',', ' ');
                    $fmtSenderBalance = number_format($newSenderBalance, 0, ',', ' ');
                    $senderMsg = "OM-Paie: Transfert de {$fmtAmount} FCFA vers {$receiverNumber}. Nouveau solde: {$fmtSenderBalance} FCFA. Ref: {$transferRef}";
                    Notification::send($sender->telephone, $senderMsg);

                    Log::info('Notification transfert envoyée (SMS - expéditeur)', [
                        'sender_id' => $sender->id ?? null,
                        'telephone' => $sender->telephone,
                        'transfer_ref' => $transferRef,
                        'amount' => $amount,
                        'new_balance' => $newSenderBalance
                    ]);
                } else {
                    Log::warning('Téléphone expéditeur manquant - notification transfert non envoyée', [
                        'sender_id' => $sender->id ?? null,
                        'transfer_ref' => $transferRef
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('Erreur envoi SMS notification transfert (expéditeur)', [
                    'sender_id' => $sender->id ?? null,
                    'telephone' => $sender->telephone ?? null,
                    'error' => $e->getMessage()
                ]);
            }

            try {
                if (!empty($receiver->telephone)) {
                    $fmtAmount = number_format($amount, 0, ',', ' ');
                    $fmtReceiverBalance = number_format($newReceiverBalance, 0, ',', ' ');
                    $receiverMsg = "OM-Paie: Vous avez reçu {$fmtAmount} FCFA de {$senderNumber}. Nouveau solde: {$fmtReceiverBalance} FCFA. Ref: {$transferRef}";
                    Notification::send($receiver->telephone, $receiverMsg);

                    Log::info('Notification transfert envoyée (SMS - destinataire)', [
                        'receiver_id' => $receiver->id ?? null,
                        'telephone' => $receiver->telephone,
                        'transfer_ref' => $transferRef,
                        'amount' => $amount,
                        'new_balance' => $newReceiverBalance
                    ]);
                } else {
                    Log::warning('Téléphone destinataire manquant - notification transfert non envoyée', [
                        'receiver_id' => $receiver->id ?? null,
                        'transfer_ref' => $transferRef
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('Erreur envoi SMS notification transfert (destinataire)', [
                    'receiver_id' => $receiver->id ?? null,
                    'telephone' => $receiver->telephone ?? null,
                    'error' => $e->getMessage()
                ]);
            }

            return [
                'success' => true,
                'reference' => $transferRef,
                'sender' => [
                    'number' => $senderNumber,
                    'name' => $sender->nom . ' ' . $sender->prenom,
                    'new_balance' => $newSenderBalance
                ],
                'receiver' => [
                    'number' => $receiverNumber,
                    'name' => $receiver->nom . ' ' . $receiver->prenom,
                    'new_balance' => $newReceiverBalance
                ],
                'amount' => $amount,
                'debit_transaction' => $debitTransaction,
                'credit_transaction' => $creditTransaction
            ];
        } catch (Exception $e) {
            // Supprimer le cache en cas d'erreur
            Cache::forget($cacheKey);
            throw $e;
        } finally {
            // Supprimer le cache après succès
            Cache::forget($cacheKey);
        }
    }

    /**
     * Vérifier si un numéro existe
     */
    public function checkUserExists(string $numero): bool
    {
        return $this->transferRepository->findUserByNumber($numero) !== null;
    }

    /**
     * Obtenir le solde d'un utilisateur
     */
    public function getUserBalance(string $numero): ?float
    {
        $user = $this->transferRepository->findUserByNumber($numero);
        if (!$user) {
            return null;
        }

        $wallet = $this->transferRepository->getUserWallet($user->id);
        return $wallet ? $wallet->balance : null;
    }

    /**
     * Obtenir l'historique des transferts d'un utilisateur
     */
    public function getUserTransferHistory(string $numero, int $page = 1, int $limit = 10): array
    {
        $user = $this->transferRepository->findUserByNumber($numero);
        if (!$user) {
            throw new Exception('Utilisateur non trouvé');
        }

        // Utiliser la nouvelle méthode avec tri par date décroissante
        $result = $this->transferRepository->getUserTransferHistory($user->id, $page, $limit);

        // Retourner un tableau avec les données paginées
        return [
            'transfers' => $result->items(),
            'pagination' => [
                'current_page' => $result->currentPage(),
                'per_page' => $result->perPage(),
                'total' => $result->total(),
                'last_page' => $result->lastPage()
            ]
        ];
    }
}
