<?php

namespace App\Listeners;

use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotificationFailedListener
{

    /**
     * Handle the event.
     *
     * @param  NotificationFailed $event
     * @return void
     * @throws \Exception
     */
    public function handle(NotificationFailed $event)
    {   
        if (array_key_exists('exception',$event->data)) {
            throw $event->data['exception'];
        }else{
            throw new \Exception("Error sending notification: ".json_encode($event->data));
        }
        
    }
}
