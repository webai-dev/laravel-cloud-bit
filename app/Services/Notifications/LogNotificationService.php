<?php
namespace App\Services\Notifications;


use App\Util\NotificationPayload;
use Illuminate\Support\Facades\Log;

class LogNotificationService implements NotificationService {

    public function send(NotificationPayload $notification) {
        Log::info($notification->toArray());
    }
}