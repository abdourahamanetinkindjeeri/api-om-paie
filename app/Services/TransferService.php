<?php

namespace App\Services;

use App\Repositories\TransferRepository;
use App\Services\Contracts\TransferServiceInterface;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Exception;

class TransferService extends BaseService implements TransferServiceInterface
{
    protected TransferRepository $transferRepository;

    public function __construct(TransferRepository $repository)
    {
        parent::__construct($repository);
        $this->transferRepository = $repository;
    }

    /**
     * Effectuer un transfert entre utilisateurs
     */
    public function transfer(string $senderNumber, string $receiverNumber, float $amount): array
    {
        // Remplacer DB::transaction par une gestion manuelle pour MongoDB standalone
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

            if (!$receiverWallet) {
                throw new Exception('Wallet du destinataire non trouvé');
            }

            // Vérifier le solde suffisant
            if ($senderWallet->balance < $amount) {
                throw new Exception('Solde insuffisant');
            }

            // Générer des références uniques pour chaque transaction
            $transferRef = 'TRF_' . Str::uuid();
            $debitRef = 'DBT_' . Str::uuid();
            $creditRef = 'CRD_' . Str::uuid();

            // Créer la transaction de débit (expéditeur)
            $debitTransaction = $this->transferRepository->createTransferTransaction([
                'wallet_id' => $senderWallet->id,
                'type' => 'transfer',
                'amount' => -$amount, // Montant négatif pour le débit
                'status' => 'success',
                'reference' => $debitRef,
                'meta' => [
                    'sender_number' => $senderNumber,
                    'receiver_number' => $receiverNumber,
                    'transfer_type' => 'debit',
                    'transfer_reference' => $transferRef
                ]
            ]);

            // Créer la transaction de crédit (destinataire)
            $creditTransaction = $this->transferRepository->createTransferTransaction([
                'wallet_id' => $receiverWallet->id,
                'type' => 'transfer',
                'amount' => $amount, // Montant positif pour le crédit
                'status' => 'success',
                'reference' => $creditRef,
                'meta' => [
                    'sender_number' => $senderNumber,
                    'receiver_number' => $receiverNumber,
                    'transfer_type' => 'credit',
                    'transfer_reference' => $transferRef
                ]
            ]);

            // Mettre à jour les soldes
            $newSenderBalance = $senderWallet->balance - $amount;
            $newReceiverBalance = $receiverWallet->balance + $amount;

            $this->transferRepository->updateWalletBalance($senderWallet->id, $newSenderBalance);
            $this->transferRepository->updateWalletBalance($receiverWallet->id, $newReceiverBalance);

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
            // En cas d'erreur, relancer l'exception
            throw $e;
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
