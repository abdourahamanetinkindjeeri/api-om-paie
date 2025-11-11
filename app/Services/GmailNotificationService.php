<?php

namespace App\Services;

use App\Services\Contracts\NotificationChannelInterface;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class GmailNotificationService implements NotificationChannelInterface
{
    public function send(string $to, string $message): bool
    {
        try {
            Log::info("GmailNotificationService: Envoi d'email", [
                'destinataire' => $to,
                'message_preview' => substr($message, 0, 100),
                'timestamp' => now()->toDateTimeString()
            ]);

            Mail::raw($message, function ($mail) use ($to) {
                $mail->to($to)
                    ->subject('Code de vérification OM-Paie')
                    ->from(config('mail.from.address'), 'OM-Paie');
            });

            Log::info("GmailNotificationService: Email envoyé avec succès", [
                'destinataire' => $to,
                'timestamp' => now()->toDateTimeString()
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("GmailNotificationService: Erreur lors de l'envoi", [
                'destinataire' => $to,
                'erreur' => $e->getMessage(),
                'timestamp' => now()->toDateTimeString()
            ]);
            return false;
        }
    }
}
