<?php
namespace App\Services\Contracts;

interface NotificationChannelInterface {
    public function send(string $to, string $message): bool;
}
