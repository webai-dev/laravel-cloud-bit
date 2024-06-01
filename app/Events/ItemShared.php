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

class ItemShared{
    
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public $share;
    public $notify = true;
    
    public function __construct(Share $share,$notify = true){
        $this->share = $share;
        $this->notify = $notify;
    }

}
