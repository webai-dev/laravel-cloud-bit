<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Activity;

class ActivityServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        \App\Models\Folder::observe(\App\Tracing\Observers\FolderObserver::class);
        \App\Models\File::observe(\App\Tracing\Observers\FileObserver::class);
        \App\Models\Bits\Bit::observe(\App\Tracing\Observers\BitObserver::class);
        \App\Models\Share::observe(\App\Tracing\Observers\ShareObserver::class);
        \App\Models\Lock::observe(\App\Tracing\Observers\LockObserver::class);
        \App\Models\Teams\Invitation::observe(\App\Tracing\Observers\TeamInvitationObserver::class);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(Activity::class, function () {
            return new Activity;
        });
    }
}
