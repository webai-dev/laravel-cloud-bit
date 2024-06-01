<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

use App\Models\Teams\Team;
use App\Models\Teams\Invitation;
use App\Models\User;


class InvitationAccepted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    
    public $invitation;
    
    public function __construct(Invitation $invitation,User $user){
        $this->invitation = $invitation;
        $this->user = $user;
    }
}
