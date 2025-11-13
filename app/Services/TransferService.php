<?php

namespace App\Services;

use App\Repositories\TransferRepository;
use App\Services\Contracts\TransferServiceInterface;
use Exception;

class TransferService extends BaseService implements TransferServiceInterface
{
    protected TransferRepository $transferRepository;
    protected TransferValidator $transferValidator;
    protected TransferProcessor $transferProcessor;
    protected TransferNotificationService $transferNotificationService;
    protected TransferHistoryService $transferHistoryService;

    public function __construct(
        TransferRepository $repository,
        TransferValidator $transferValidator,
        TransferProcessor $transferProcessor,
        TransferNotificationService $transferNotificationService,
        TransferHistoryService $transferHistoryService
    ) {
        parent::__construct($repository);
        $this->transferRepository = $repository;
        $this->transferValidator = $transferValidator;
        $this->transferProcessor = $transferProcessor;
        $this->transferNotificationService = $transferNotificationService;
        $this->transferHistoryService = $transferHistoryService;
    }

    /**
     * Effectuer un transfert entre utilisateurs avec validation et traitement spécialisés
     */
    public function transfer(string $senderNumber, string $receiverNumber, float $amount, array $metadata = []): array
    {
        // 1. Validation complète de la demande de transfert
        $validation = $this->transferValidator->validateTransferRequest($senderNumber, $receiverNumber, $amount, $metadata);
        if (!$validation['valid']) {
            throw new Exception('Validation échouée : ' . implode(', ', $validation['errors']));
        }

        // 2. Récupération des entités nécessaires
        $sender = $this->transferRepository->findUserByNumber($senderNumber);
        $receiver = $this->transferRepository->findUserByNumber($receiverNumber);
        $senderWallet = $this->transferRepository->getUserWallet($sender->id);
        $receiverWallet = $this->transferRepository->getUserWallet($receiver->id);

        // 3. Validation des soldes
        $balanceErrors = $this->transferValidator->validateSufficientBalance($senderWallet->id, $amount);
        if (!empty($balanceErrors)) {
            throw new Exception('Validation soldes échouée : ' . implode(', ', $balanceErrors));
        }

        $receiverErrors = $this->transferValidator->validateReceiverCanReceive($receiverWallet->id, $amount);
        if (!empty($receiverErrors)) {
            throw new Exception('Validation destinataire échouée : ' . implode(', ', $receiverErrors));
        }

        // 4. Définition du verrou anti-dédoublement
        $cacheKey = $this->transferProcessor->setProcessingLock($senderNumber, $receiverNumber, $amount);

        try {
            // 5. Traitement du transfert
            $result = $this->transferProcessor->processTransfer(
                $sender,
                $receiver,
                $senderWallet,
                $receiverWallet,
                $amount,
                $metadata
            );

            // 6. Envoi des notifications de succès
            $this->transferNotificationService->sendTransferSuccessNotifications(
                $sender,
                $receiver,
                $amount,
                $result['sender']['new_balance'],
                $result['receiver']['new_balance'],
                $result['reference']
            );

            return array_merge($result, ['success' => true]);

        } catch (Exception $e) {
            // Envoi de notification d'échec
            $this->transferNotificationService->sendTransferFailureNotification(
                $sender,
                $receiverNumber,
                $amount,
                $e->getMessage()
            );

            throw $e;

        } finally {
            // Libération du verrou
            $this->transferProcessor->releaseProcessingLock($cacheKey);
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
        return $this->transferHistoryService->getUserTransferHistory($numero, $page, $limit);
    }
}
