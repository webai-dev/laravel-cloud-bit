<?php

namespace App\Channels;


use App\Notifications\YBitNotification;
use App\Services\Notifications\NotificationService;

class NotificationChannel {
    protected $service;

    public function __construct(NotificationService $service) {
        $this->service = $service;
    }

    public function send($notifiable, YBitNotification $notification) {
        $payload = $notification->toNotification($notifiable);
        $this->service->send($payload);
    }
}