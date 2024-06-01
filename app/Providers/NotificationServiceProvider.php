<?php

namespace App\Providers;

use App\Services\Notifications\FirestoreNotificationService;
use App\Services\Notifications\LogNotificationService;
use App\Services\Notifications\NotificationService;
use Google\Cloud\Firestore\FirestoreClient;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider {
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot() {
        $this->app->singleton(NotificationService::class, function () {
            if (config('notifications.desktop.enabled')) {
                return new FirestoreNotificationService(app()->make(FirestoreClient::class));
            } else {
                return new LogNotificationService();
            }
        });
    }
}
