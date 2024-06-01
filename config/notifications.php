<?php

/*
|--------------------------------------------------------------------------
| Notifications configuration
|--------------------------------------------------------------------------
|
| Configuration details for notifications
|
*/

return [
    "desktop" => [
        /*
        |--------------------------------------------------------------------------
        | Desktop notifications switch
        |--------------------------------------------------------------------------
        |
        | Disables/Enables desktop notifications
        |
        */
        "enabled" => env('NOTIFICATIONS_ENABLED', false),
    ]
];