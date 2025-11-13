<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Facades\Notification;
use Exception;

/**
 * Service de notification spécialisé pour les transferts
 */
class TransferNotificationService
{
    /**
     * Envoie les notifications de transfert réussi aux deux parties
     */
    public function sendTransferSuccessNotifications(object $sender, object $receiver, float $amount, float $senderNewBalance, float $receiverNewBalance, string $transferRef): array
    {
        $results = [
            'sender' => false,
            'receiver' => false
        ];

        // Notification à l'expéditeur
        $results['sender'] = $this->sendTransferSuccessToSender($sender, $receiver->telephone, $amount, $senderNewBalance, $transferRef);

        // Notification au destinataire
        $results['receiver'] = $this->sendTransferSuccessToReceiver($receiver, $sender->telephone, $amount, $receiverNewBalance, $transferRef);

        return $results;
    }

    /**
     * Envoie une notification de transfert réussi à l'expéditeur
     */
    public function sendTransferSuccessToSender(object $sender, string $receiverNumber, float $amount, float $newBalance, string $transferRef): bool
    {
        if (empty($sender->telephone)) {
            Log::warning('Téléphone expéditeur manquant - notification transfert non envoyée', [
                'sender_id' => $sender->id ?? null,
                'transfer_ref' => $transferRef
            ]);
            return false;
        }

        try {
            $fmtAmount = number_format($amount, 0, ',', ' ');
            $fmtSenderBalance = number_format($newBalance, 0, ',', ' ');
            $senderMsg = "OM-Paie: Transfert de {$fmtAmount} FCFA vers {$receiverNumber}. Nouveau solde: {$fmtSenderBalance} FCFA. Ref: {$transferRef}";

            $result = Notification::send($sender->telephone, $senderMsg);

            Log::info('Notification transfert envoyée (SMS - expéditeur)', [
                'sender_id' => $sender->id ?? null,
                'telephone' => $sender->telephone,
                'transfer_ref' => $transferRef,
                'amount' => $amount,
                'new_balance' => $newBalance,
                'notification_result' => $result
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Erreur envoi SMS notification transfert (expéditeur)', [
                'sender_id' => $sender->id ?? null,
                'telephone' => $sender->telephone ?? null,
                'transfer_ref' => $transferRef,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Envoie une notification de transfert réussi au destinataire
     */
    public function sendTransferSuccessToReceiver(object $receiver, string $senderNumber, float $amount, float $newBalance, string $transferRef): bool
    {
        if (empty($receiver->telephone)) {
            Log::warning('Téléphone destinataire manquant - notification transfert non envoyée', [
                'receiver_id' => $receiver->id ?? null,
                'transfer_ref' => $transferRef
            ]);
            return false;
        }

        try {
            $fmtAmount = number_format($amount, 0, ',', ' ');
            $fmtReceiverBalance = number_format($newBalance, 0, ',', ' ');
            $receiverMsg = "OM-Paie: Vous avez reçu {$fmtAmount} FCFA de {$senderNumber}. Nouveau solde: {$fmtReceiverBalance} FCFA. Ref: {$transferRef}";

            $result = Notification::send($receiver->telephone, $receiverMsg);

            Log::info('Notification transfert envoyée (SMS - destinataire)', [
                'receiver_id' => $receiver->id ?? null,
                'telephone' => $receiver->telephone,
                'transfer_ref' => $transferRef,
                'amount' => $amount,
                'new_balance' => $newBalance,
                'notification_result' => $result
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Erreur envoi SMS notification transfert (destinataire)', [
                'receiver_id' => $receiver->id ?? null,
                'telephone' => $receiver->telephone ?? null,
                'transfer_ref' => $transferRef,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Envoie une notification d'échec de transfert
     */
    public function sendTransferFailureNotification(object $sender, string $receiverNumber, float $amount, string $reason): bool
    {
        if (empty($sender->telephone)) {
            Log::warning('Téléphone expéditeur manquant - notification échec transfert non envoyée', [
                'sender_id' => $sender->id ?? null
            ]);
            return false;
        }

        try {
            $fmtAmount = number_format($amount, 0, ',', ' ');
            $message = "OM-Paie: Échec transfert de {$fmtAmount} FCFA vers {$receiverNumber}. Raison: {$reason}";

            $result = Notification::send($sender->telephone, $message);

            Log::info('Notification échec transfert envoyée (SMS)', [
                'sender_id' => $sender->id ?? null,
                'telephone' => $sender->telephone,
                'amount' => $amount,
                'receiver' => $receiverNumber,
                'reason' => $reason,
                'notification_result' => $result
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Erreur envoi SMS notification échec transfert', [
                'sender_id' => $sender->id ?? null,
                'telephone' => $sender->telephone ?? null,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Envoie une notification de transfert en attente
     */
    public function sendTransferPendingNotification(object $sender, string $receiverNumber, float $amount, string $transferRef): bool
    {
        if (empty($sender->telephone)) {
            Log::warning('Téléphone expéditeur manquant - notification transfert en attente non envoyée', [
                'sender_id' => $sender->id ?? null,
                'transfer_ref' => $transferRef
            ]);
            return false;
        }

        try {
            $fmtAmount = number_format($amount, 0, ',', ' ');
            $message = "OM-Paie: Transfert de {$fmtAmount} FCFA vers {$receiverNumber} en cours de traitement. Ref: {$transferRef}";

            $result = Notification::send($sender->telephone, $message);

            Log::info('Notification transfert en attente envoyée (SMS)', [
                'sender_id' => $sender->id ?? null,
                'telephone' => $sender->telephone,
                'transfer_ref' => $transferRef,
                'amount' => $amount,
                'receiver' => $receiverNumber,
                'notification_result' => $result
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Erreur envoi SMS notification transfert en attente', [
                'sender_id' => $sender->id ?? null,
                'telephone' => $sender->telephone ?? null,
                'transfer_ref' => $transferRef,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Envoie une notification d'annulation de transfert
     */
    public function sendTransferCancellationNotification(object $sender, object $receiver, float $amount, string $transferRef, string $reason): array
    {
        $results = [
            'sender' => false,
            'receiver' => false
        ];

        // Notification à l'expéditeur
        if (!empty($sender->telephone)) {
            try {
                $fmtAmount = number_format($amount, 0, ',', ' ');
                $message = "OM-Paie: Transfert de {$fmtAmount} FCFA vers {$receiver->telephone} annulé. Raison: {$reason}. Ref: {$transferRef}";

                $result = Notification::send($sender->telephone, $message);
                $results['sender'] = true;

                Log::info('Notification annulation transfert envoyée (SMS - expéditeur)', [
                    'sender_id' => $sender->id ?? null,
                    'telephone' => $sender->telephone,
                    'transfer_ref' => $transferRef,
                    'amount' => $amount,
                    'reason' => $reason,
                    'notification_result' => $result
                ]);
            } catch (\Throwable $e) {
                Log::error('Erreur envoi SMS notification annulation transfert (expéditeur)', [
                    'sender_id' => $sender->id ?? null,
                    'telephone' => $sender->telephone ?? null,
                    'transfer_ref' => $transferRef,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Notification au destinataire
        if (!empty($receiver->telephone)) {
            try {
                $fmtAmount = number_format($amount, 0, ',', ' ');
                $message = "OM-Paie: Transfert de {$fmtAmount} FCFA de {$sender->telephone} annulé. Raison: {$reason}. Ref: {$transferRef}";

                $result = Notification::send($receiver->telephone, $message);
                $results['receiver'] = true;

                Log::info('Notification annulation transfert envoyée (SMS - destinataire)', [
                    'receiver_id' => $receiver->id ?? null,
                    'telephone' => $receiver->telephone,
                    'transfer_ref' => $transferRef,
                    'amount' => $amount,
                    'reason' => $reason,
                    'notification_result' => $result
                ]);
            } catch (\Throwable $e) {
                Log::error('Erreur envoi SMS notification annulation transfert (destinataire)', [
                    'receiver_id' => $receiver->id ?? null,
                    'telephone' => $receiver->telephone ?? null,
                    'transfer_ref' => $transferRef,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    /**
     * Envoie une notification de remboursement de transfert
     */
    public function sendTransferRefundNotification(object $sender, float $amount, float $newBalance, string $originalTransferRef, string $reason): bool
    {
        if (empty($sender->telephone)) {
            Log::warning('Téléphone expéditeur manquant - notification remboursement transfert non envoyée', [
                'sender_id' => $sender->id ?? null,
                'original_transfer_ref' => $originalTransferRef
            ]);
            return false;
        }

        try {
            $fmtAmount = number_format($amount, 0, ',', ' ');
            $fmtBalance = number_format($newBalance, 0, ',', ' ');
            $message = "OM-Paie: Remboursement de {$fmtAmount} FCFA pour le transfert {$originalTransferRef}. Nouveau solde: {$fmtBalance} FCFA. Raison: {$reason}";

            $result = Notification::send($sender->telephone, $message);

            Log::info('Notification remboursement transfert envoyée (SMS)', [
                'sender_id' => $sender->id ?? null,
                'telephone' => $sender->telephone,
                'original_transfer_ref' => $originalTransferRef,
                'amount' => $amount,
                'new_balance' => $newBalance,
                'reason' => $reason,
                'notification_result' => $result
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Erreur envoi SMS notification remboursement transfert', [
                'sender_id' => $sender->id ?? null,
                'telephone' => $sender->telephone ?? null,
                'original_transfer_ref' => $originalTransferRef,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }
}
