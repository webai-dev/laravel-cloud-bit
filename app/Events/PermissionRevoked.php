<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

use App\Models\Share;

class PermissionRevoked{
    
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $share;
    
    public function __construct(Share $share){
        $this->share = $share;
    }
}
