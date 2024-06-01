<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider{

    protected $listen = [
        'Illuminate\Auth\Events\Registered' => [
            'App\Listeners\UserRegisteredListener',
        ],
        'Illuminate\Notifications\Events\NotificationFailed' => [
            'App\Listeners\NotificationFailedListener'
        ],
        'App\Events\PermissionRevoked' => [
            'App\Listeners\PermissionRevokedListener'
        ],
        'App\Events\TeamDeleted' => [
            'App\Listeners\TeamDeletedListener'
        ],
        'App\Events\TeamUserRemoved' => [
            'App\Listeners\TeamUserRemovedListener'
        ],
        'App\Events\ItemShared' => [
            'App\Listeners\ItemSharedListener'
        ],
        'App\Events\InvitationAccepted' => [
            'App\Listeners\InvitationAcceptedListener'
        ],
        'App\Events\Subscriptions\SubscriptionUpdated' => [
            'App\Listeners\Subscriptions\SubscriptionUpdatedListener'
        ],
        'App\Events\Subscriptions\SubscriptionCanceled' => [
            'App\Listeners\Subscriptions\SubscriptionCanceledListener'
        ],

    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot(){
        parent::boot();

        //
    }
}
