<?php

namespace App\Jobs;

use App\Services\Contracts\NotificationChannelInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $channels;
    protected string $to;
    protected string $message;

    /**
     * Create a new job instance.
     */
    public function __construct(array $channels, string $to, string $message)
    {
        $this->channels = $channels;
        $this->to = $to;
        $this->message = $message;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            foreach ($this->channels as $channel) {
                if ($channel instanceof NotificationChannelInterface) {
                    $channel->send($this->to, $this->message);
                }
            }

            Log::info('Notification envoyée via job', [
                'to' => $this->to,
                'channels_count' => count($this->channels)
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de notification via job', [
                'to' => $this->to,
                'error' => $e->getMessage()
            ]);

            throw $e; // Relancer pour retry
        }
    }
}
