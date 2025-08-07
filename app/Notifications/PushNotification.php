<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;

class PushNotification extends Notification
{
    use Queueable;

    public $title;
    public $body;

    public function __construct($title, $body)
    {
        $this->title = $title;
        $this->body = $body;
    }

    public function via($notifiable)
    {
        return ['webpush'];
    }

    public function toWebPush($notifiable, $notification)
    {
        return WebPushMessage::create()
            ->title($this->title)
            ->body($this->body)
            ->icon('/laravelpwa/icons/icon-192x192.png') // ícone do app
            ->action('Abrir app', '/')
            ->data(['url' => '/']);
    }
}
