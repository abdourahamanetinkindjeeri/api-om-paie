<?php
namespace App\Services;

use App\Services\Contracts\NotificationChannelInterface;
use Illuminate\Support\Facades\Mail;

class GmailNotificationService implements NotificationChannelInterface {
    public function send(string $to, string $message): bool {
        Mail::raw($message, function($mail) use ($to) {
            $mail->to($to)->subject('Notification');
        });
        return true;
    }
}
