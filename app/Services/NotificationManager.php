<?php
namespace App\Services;

use App\Jobs\SendNotificationJob;

class NotificationManager {
    protected array $channels;

    public function __construct(array $channels) {
        $this->channels = $channels;
    }

    public function send(string $to, string $message): void {
        // Dispatch le job pour envoi asynchrone
        SendNotificationJob::dispatch($this->channels, $to, $message);
    }

    /**
     * Envoi synchrone pour les cas urgents
     */
    public function sendSync(string $to, string $message): void {
        foreach ($this->channels as $channel) {
            $channel->send($to, $message);
        }
    }
}
