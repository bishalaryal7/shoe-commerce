<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class StoreUpdate extends Notification
{
    public function __construct(
        private string $title,
        private string $message,
        private string $url,
        private string $category,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'category' => $this->category,
        ];
    }
}
