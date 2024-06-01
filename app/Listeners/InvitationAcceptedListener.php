<?php

namespace App\Listeners;

use Carbon\Carbon;

use App\Enums\Roles;
use App\Events\InvitationAccepted;

use App\Models\Share;
use App\Models\Teams\TeamShareable;
use App\Notifications\InvitationAcceptedNotification;
use App\Sharing\ShareManager;

class InvitationAcceptedListener{

    public function handle(InvitationAccepted $event){
        $invitation = $event->invitation;
        $team = $invitation->team;
        $user = $event->user;

        $invitation->team->users()->attach($user->id,['created_at' => Carbon::now()]);

        if ($invitation->role->label != Roles::GUEST){
            $shares = $team->shareables->filter(function(TeamShareable $shareable) {
                return $shareable->shareable !== NULL;
            })->map(function(TeamShareable $shareable) use($user){
                $attributes = array_merge(
                    array_except($shareable->getAttributes(),'id'),
                    ['user_id' => $user->id,]
                );
                return new Share($attributes);
            });

            ShareManager::store($shares->all(),false);
        }

        $user->roles()->attach($invitation->role_id,['team_id' => $team->id]);
        $user->notify(new InvitationAcceptedNotification($invitation));
    }
}
