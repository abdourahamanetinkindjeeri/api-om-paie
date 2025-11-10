<?php
namespace App\Services;


class NotificationManager {
    protected array $channels;

    public function __construct(array $channels) {
        $this->channels = $channels;
    }

    public function send(string $to, string $message): void {
        foreach ($this->channels as $channel) {
            $channel->send($to, $message);
        }
    }
}
