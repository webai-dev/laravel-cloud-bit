<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

use App\Models\User;
use App\Models\Teams\Team;

class TeamUserRemoved{
    
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $team;
    
    public function __construct(User $user,Team $team){
        $this->user = $user;
        $this->team = $team;
    }
}
