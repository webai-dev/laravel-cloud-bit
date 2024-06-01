<?php

namespace App\Providers;

use App\Services\Bits\BitService;
use App\Services\Bits\BitServiceImpl;
use App\Services\Bits\VirtualBitService;
use App\Services\Notifications\FirestoreNotificationService;
use App\Services\Notifications\LogNotificationService;
use App\Services\Notifications\NotificationService;
use Google\Cloud\Firestore\FirestoreClient;
use Illuminate\Support\ServiceProvider;

class BitServiceProvider extends ServiceProvider {
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot() {
        $this->app->singleton(BitService::class, function() {
            if (config('app.bits_enabled')) {
                return new BitServiceImpl();
            } else {
                return new VirtualBitService();
            }
        });
    }
}
