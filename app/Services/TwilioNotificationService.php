<?php

namespace App\Services;

use App\Services\Contracts\NotificationChannelInterface;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;
use Twilio\Exceptions\TwilioException;

class TwilioNotificationService implements NotificationChannelInterface
{
    protected Client $client;
    protected string $fromPhone;

    public function __construct()
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $this->fromPhone = config('services.twilio.from');

        if (!$sid || !$token || !$this->fromPhone) {
            Log::warning("TwilioNotificationService: Configuration Twilio incomplète", [
                'sid_present' => !empty($sid),
                'token_present' => !empty($token),
                'from_present' => !empty($this->fromPhone),
            ]);
        }

        $this->client = new Client($sid, $token);
    }

    public function send(string $to, string $message): bool
    {
        // Vérifier si c'est un numéro de téléphone valide (format international)
        if (!preg_match('/^\+[1-9]\d{1,14}$/', $to)) {
            Log::debug("TwilioNotificationService: Destination ignorée (pas un numéro de téléphone)", [
                'destinataire' => $to
            ]);
            return true; // Retourner true pour ne pas bloquer les autres canaux
        }

        try {
            Log::info("TwilioNotificationService: Envoi de SMS", [
                'destinataire' => $to,
                'message_preview' => substr($message, 0, 100),
                'from' => $this->fromPhone,
                'timestamp' => now()->toDateTimeString()
            ]);

            // Envoi du SMS via Twilio
            $sms = $this->client->messages->create(
                $to, // Numéro de destination
                [
                    'from' => $this->fromPhone,
                    'body' => $message
                ]
            );

            Log::info("TwilioNotificationService: SMS envoyé avec succès", [
                'destinataire' => $to,
                'message_sid' => $sms->sid,
                'status' => $sms->status,
                'timestamp' => now()->toDateTimeString()
            ]);

            return true;
        } catch (TwilioException $e) {
            Log::error("TwilioNotificationService: Erreur Twilio", [
                'destinataire' => $to,
                'erreur' => $e->getMessage(),
                'code' => $e->getCode(),
                'timestamp' => now()->toDateTimeString()
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error("TwilioNotificationService: Erreur lors de l'envoi", [
                'destinataire' => $to,
                'erreur' => $e->getMessage(),
                'type' => get_class($e),
                'trace' => $e->getTraceAsString(),
                'timestamp' => now()->toDateTimeString()
            ]);
            return false;
        }
    }
}
