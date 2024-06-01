<?php

namespace App\Services\Notifications;

use App\Util\NotificationPayload;
use Google\Cloud\Firestore\FirestoreClient;

class FirestoreNotificationService implements NotificationService {
    protected $client;

    public function __construct(FirestoreClient $client) {
        $this->client = $client;
    }

    public function send(NotificationPayload $notification) {
        $path = $this->getPath($notification);
        $this->client->collection($path)
            ->add($notification->toArray(['user_id', 'team_id']));
    }

    private function getPath($notification) {
        if ($notification->user_id !== null) {
            return "users/$notification->user_id/teams/$notification->team_id/notifications";
        }

        return "general";
    }
}