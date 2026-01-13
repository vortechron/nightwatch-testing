<?php

namespace Vortechron\NightwatchTesting\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NightwatchTestNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $message = 'Nightwatch test notification'
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => $this->message,
            'triggered_at' => now()->toIso8601String(),
        ];
    }
}
