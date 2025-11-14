<?php

namespace App\Services;

use App\Repositories\PaymentRepository;
use App\Services\Contracts\PaymentServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Exception;

class PaymentService extends BaseService implements PaymentServiceInterface
{
    protected PaymentRepository $paymentRepository;
    protected PaymentValidator $paymentValidator;
    protected PaymentProcessor $paymentProcessor;
    protected PaymentNotificationService $paymentNotificationService;
    protected PaymentHistoryService $paymentHistoryService;

    public function __construct(
        PaymentRepository $repository,
        PaymentValidator $paymentValidator,
        PaymentProcessor $paymentProcessor,
        PaymentNotificationService $paymentNotificationService,
        PaymentHistoryService $paymentHistoryService
    ) {
        parent::__construct($repository);
        $this->paymentRepository = $repository;
        $this->paymentValidator = $paymentValidator;
        $this->paymentProcessor = $paymentProcessor;
        $this->paymentNotificationService = $paymentNotificationService;
        $this->paymentHistoryService = $paymentHistoryService;
    }

    /**
     * Effectuer un paiement vers un marchand avec validation et traitement spécialisés
     */
    public function payMerchant(object $user, string $merchantCode, float $amount, array $metadata = [], ?string $userWalletId = null): array
    {
        // 1. Validation complète de la demande de paiement
        $validation = $this->paymentValidator->validatePaymentRequest($user, $merchantCode, $amount, $metadata);
        if (!$validation['valid']) {
            throw new Exception('Validation échouée : ' . implode(', ', $validation['errors']));
        }

        // 2. Récupération des entités nécessaires
        $merchant = $this->paymentRepository->findMerchantByCode($merchantCode);

        // Utiliser le wallet spécifié ou le wallet de l'utilisateur
        if ($userWalletId) {
            $userWallet = $this->paymentRepository->findWalletById($userWalletId);
            if (!$userWallet || $userWallet->user_id !== $user->id) {
                throw new Exception('Wallet spécifié invalide ou n\'appartient pas à l\'utilisateur');
            }
        } else {
            $userWallet = $user->wallet;
        }

        $merchantWallet = $this->paymentRepository->getMerchantWallet($merchant->id);

        // 3. Validation des soldes
        $balanceErrors = $this->paymentValidator->validateSufficientBalance($userWallet->id, $amount);
        if (!empty($balanceErrors)) {
            throw new Exception('Validation soldes échouée : ' . implode(', ', $balanceErrors));
        }

        $merchantErrors = $this->paymentValidator->validateMerchantCanReceive($merchantWallet->id, $amount);
        if (!empty($merchantErrors)) {
            throw new Exception('Validation marchand échouée : ' . implode(', ', $merchantErrors));
        }

        // 4. Définition du verrou anti-dédoublement
        $cacheKey = $this->paymentProcessor->setProcessingLock($user, $merchantCode, $amount);

        try {
            // 5. Traitement du paiement
            $result = $this->paymentProcessor->processPayment(
                $user,
                $merchant,
                $userWallet,
                $merchantWallet,
                $amount,
                $metadata
            );

            // 6. Envoi de la notification de succès
            $this->paymentNotificationService->sendPaymentSuccessNotification(
                $user,
                $merchant->name,
                $amount,
                $result['user']['new_balance'],
                $result['reference']
            );

            return array_merge($result, ['success' => true]);

        } catch (Exception $e) {
            // Envoi de notification d'échec
            $this->paymentNotificationService->sendPaymentFailureNotification(
                $user,
                $merchant->name,
                $amount,
                $e->getMessage()
            );

            throw $e;

        } finally {
            // Libération du verrou
            $this->paymentProcessor->releaseProcessingLock($cacheKey);
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
        return $this->paymentHistoryService->getUserPaymentHistory($userPhone, $page, $limit);
    }

    public function getMerchantPaymentHistory(string $merchantCode, int $page = 1, int $limit = 10): LengthAwarePaginator
    {
        return $this->paymentHistoryService->getMerchantPaymentHistory($merchantCode, $page, $limit);
    }
}
