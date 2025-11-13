<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Facades\Notification;
use Exception;

/**
 * Service de notification spécialisé pour les paiements
 */
class PaymentNotificationService
{
    /**
     * Envoie une notification de paiement réussi à l'utilisateur
     */
    public function sendPaymentSuccessNotification(object $user, string $merchantName, float $amount, float $newBalance, string $paymentRef): bool
    {
        if (empty($user->telephone)) {
            Log::warning('Téléphone utilisateur manquant - notification paiement non envoyée', [
                'user_id' => $user->id ?? null,
                'payment_ref' => $paymentRef
            ]);
            return false;
        }

        try {
            $fmtAmount = number_format($amount, 0, ',', ' ');
            $fmtBalance = number_format($newBalance, 0, ',', ' ');
            $message = "OM-Paie: Paiement de {$fmtAmount} FCFA chez {$merchantName}. Nouveau solde: {$fmtBalance} FCFA. Ref: {$paymentRef}";

            $result = Notification::send($user->telephone, $message);

            Log::info('Notification paiement envoyée (SMS)', [
                'user_id' => $user->id ?? null,
                'telephone' => $user->telephone,
                'payment_ref' => $paymentRef,
                'amount' => $amount,
                'new_balance' => $newBalance,
                'notification_result' => $result
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Erreur envoi SMS notification paiement', [
                'user_id' => $user->id ?? null,
                'telephone' => $user->telephone ?? null,
                'payment_ref' => $paymentRef,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Envoie une notification d'échec de paiement
     */
    public function sendPaymentFailureNotification(object $user, string $merchantName, float $amount, string $reason): bool
    {
        if (empty($user->telephone)) {
            Log::warning('Téléphone utilisateur manquant - notification échec paiement non envoyée', [
                'user_id' => $user->id ?? null
            ]);
            return false;
        }

        try {
            $fmtAmount = number_format($amount, 0, ',', ' ');
            $message = "OM-Paie: Échec paiement de {$fmtAmount} FCFA chez {$merchantName}. Raison: {$reason}";

            $result = Notification::send($user->telephone, $message);

            Log::info('Notification échec paiement envoyée (SMS)', [
                'user_id' => $user->id ?? null,
                'telephone' => $user->telephone,
                'amount' => $amount,
                'merchant' => $merchantName,
                'reason' => $reason,
                'notification_result' => $result
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Erreur envoi SMS notification échec paiement', [
                'user_id' => $user->id ?? null,
                'telephone' => $user->telephone ?? null,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Envoie une notification de paiement en attente
     */
    public function sendPaymentPendingNotification(object $user, string $merchantName, float $amount, string $paymentRef): bool
    {
        if (empty($user->telephone)) {
            Log::warning('Téléphone utilisateur manquant - notification paiement en attente non envoyée', [
                'user_id' => $user->id ?? null,
                'payment_ref' => $paymentRef
            ]);
            return false;
        }

        try {
            $fmtAmount = number_format($amount, 0, ',', ' ');
            $message = "OM-Paie: Paiement de {$fmtAmount} FCFA chez {$merchantName} en cours de traitement. Ref: {$paymentRef}";

            $result = Notification::send($user->telephone, $message);

            Log::info('Notification paiement en attente envoyée (SMS)', [
                'user_id' => $user->id ?? null,
                'telephone' => $user->telephone,
                'payment_ref' => $paymentRef,
                'amount' => $amount,
                'merchant' => $merchantName,
                'notification_result' => $result
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Erreur envoi SMS notification paiement en attente', [
                'user_id' => $user->id ?? null,
                'telephone' => $user->telephone ?? null,
                'payment_ref' => $paymentRef,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Envoie une notification de remboursement
     */
    public function sendRefundNotification(object $user, float $amount, float $newBalance, string $reason): bool
    {
        if (empty($user->telephone)) {
            Log::warning('Téléphone utilisateur manquant - notification remboursement non envoyée', [
                'user_id' => $user->id ?? null
            ]);
            return false;
        }

        try {
            $fmtAmount = number_format($amount, 0, ',', ' ');
            $fmtBalance = number_format($newBalance, 0, ',', ' ');
            $message = "OM-Paie: Remboursement de {$fmtAmount} FCFA effectué. Nouveau solde: {$fmtBalance} FCFA. Raison: {$reason}";

            $result = Notification::send($user->telephone, $message);

            Log::info('Notification remboursement envoyée (SMS)', [
                'user_id' => $user->id ?? null,
                'telephone' => $user->telephone,
                'amount' => $amount,
                'new_balance' => $newBalance,
                'reason' => $reason,
                'notification_result' => $result
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Erreur envoi SMS notification remboursement', [
                'user_id' => $user->id ?? null,
                'telephone' => $user->telephone ?? null,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Envoie une notification groupée pour plusieurs paiements
     */
    public function sendBulkPaymentNotification(object $user, array $payments, float $totalAmount, float $newBalance): bool
    {
        if (empty($user->telephone)) {
            Log::warning('Téléphone utilisateur manquant - notification paiements groupés non envoyée', [
                'user_id' => $user->id ?? null
            ]);
            return false;
        }

        try {
            $fmtTotal = number_format($totalAmount, 0, ',', ' ');
            $fmtBalance = number_format($newBalance, 0, ',', ' ');
            $paymentCount = count($payments);

            $message = "OM-Paie: {$paymentCount} paiement(s) traité(s) pour un total de {$fmtTotal} FCFA. Nouveau solde: {$fmtBalance} FCFA";

            $result = Notification::send($user->telephone, $message);

            Log::info('Notification paiements groupés envoyée (SMS)', [
                'user_id' => $user->id ?? null,
                'telephone' => $user->telephone,
                'payment_count' => $paymentCount,
                'total_amount' => $totalAmount,
                'new_balance' => $newBalance,
                'notification_result' => $result
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Erreur envoi SMS notification paiements groupés', [
                'user_id' => $user->id ?? null,
                'telephone' => $user->telephone ?? null,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }
}
