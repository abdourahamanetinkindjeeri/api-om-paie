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
        $original = $to;

        // Normalisation: enlever espaces, tirets, points
        $normalized = preg_replace('/[\s\-.]/', '', trim($to));

        // Convertir le préfixe "00" en "+"
        if (strpos($normalized, '00') === 0) {
            $normalized = '+' . substr($normalized, 2);
        }

        // Essayer de normaliser les numéros Sénégal vers E.164 (+221)
        if (!preg_match('/^\+[1-9]\d{1,14}$/', $normalized)) {
            $digits = preg_replace('/\D/', '', $normalized);
            if (preg_match('/^(?:221)?(77|76|78|75|70|71)\d{7}$/', $digits)) {
                $local = substr($digits, -9);
                $normalized = '+221' . $local;
            }
        }

        // Validation finale E.164
        if (!preg_match('/^\+[1-9]\d{1,14}$/', $normalized)) {
            Log::debug("TwilioNotificationService: Destination ignorée (non E.164)", [
                'destinataire_original' => $original,
                'destinataire_normalise' => $normalized,
            ]);
            return true; // ne bloque pas les autres canaux
        }

        try {
            Log::info("TwilioNotificationService: Envoi de SMS", [
                'destinataire' => $normalized,
                'destinataire_original' => $original,
                'message_preview' => substr($message, 0, 100),
                'from' => $this->fromPhone,
                'timestamp' => now()->toDateTimeString()
            ]);

            // Envoi du SMS via Twilio
            $sms = $this->client->messages->create(
                $normalized,
                [
                    'from' => $this->fromPhone,
                    'body' => $message
                ]
            );

            Log::info("TwilioNotificationService: SMS envoyé avec succès", [
                'destinataire' => $normalized,
                'message_sid' => $sms->sid,
                'status' => $sms->status,
                'timestamp' => now()->toDateTimeString()
            ]);

            return true;
        } catch (TwilioException $e) {
            Log::error("TwilioNotificationService: Erreur Twilio", [
                'destinataire' => $normalized ?? $original,
                'erreur' => $e->getMessage(),
                'code' => $e->getCode(),
                'timestamp' => now()->toDateTimeString()
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error("TwilioNotificationService: Erreur lors de l'envoi", [
                'destinataire' => $normalized ?? $original,
                'erreur' => $e->getMessage(),
                'type' => get_class($e),
                'trace' => $e->getTraceAsString(),
                'timestamp' => now()->toDateTimeString()
            ]);
            return false;
        }
    }
}
