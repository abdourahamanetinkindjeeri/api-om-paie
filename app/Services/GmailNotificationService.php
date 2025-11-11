<?php

namespace App\Services;

use App\Services\Contracts\NotificationChannelInterface;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

class GmailNotificationService implements NotificationChannelInterface
{
    public function send(string $to, string $message): bool
    {
        try {
            $username = config('mail.mailers.smtp.username');
            $password = config('mail.mailers.smtp.password');
            $host = config('mail.mailers.smtp.host');
            $port = config('mail.mailers.smtp.port');

            Log::info("GmailNotificationService: Envoi d'email SMTP direct", [
                'destinataire' => $to,
                'message_preview' => substr($message, 0, 100),
                'smtp_host' => $host,
                'smtp_port' => $port,
                'smtp_username' => $username,
                'password_length' => strlen($password),
                'timestamp' => now()->toDateTimeString()
            ]);

            // Création du DSN SMTP - NE PAS utiliser urlencode pour le mot de passe Gmail App Password
            // Gmail App Password format: xxxx xxxx xxxx xxxx (avec espaces)
            $dsn = sprintf(
                'smtp://%s:%s@%s:%d',
                urlencode($username),
                str_replace(' ', '', $password), // Retirer les espaces du mot de passe
                $host,
                $port
            );

            // Création du transport et du mailer
            $transport = Transport::fromDsn($dsn);
            $mailer = new Mailer($transport);

            // Création de l'email
            $email = (new Email())
                ->from(config('mail.from.address'))
                ->to($to)
                ->subject('Code de vérification OM-Paie')
                ->text($message);

            // Envoi immédiat
            $mailer->send($email);

            Log::info("GmailNotificationService: Email envoyé avec succès via SMTP", [
                'destinataire' => $to,
                'timestamp' => now()->toDateTimeString()
            ]);
            
            return true;

        } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
            Log::error("GmailNotificationService: Erreur SMTP Transport", [
                'destinataire' => $to,
                'erreur' => $e->getMessage(),
                'debug' => $e->getDebug() ?? 'N/A',
                'timestamp' => now()->toDateTimeString()
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error("GmailNotificationService: Erreur lors de l'envoi", [
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
