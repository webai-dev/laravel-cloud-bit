<?php

namespace App\Services\Notifications;

use App\Util\NotificationPayload;

interface NotificationService {
    public function send(NotificationPayload $notification);
}