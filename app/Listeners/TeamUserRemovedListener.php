<?php

namespace App\Listeners;

use App\Events\TeamUserRemoved;

use App\Models\Folder;
use App\Models\Share;

class TeamUserRemovedListener {

    public function handle(TeamUserRemoved $event) {
        $user = $event->user;
        $team = $event->team;

        //Transfer all items to the owner of the team

        $files = $user->files()->where('team_id', $team->id)->get();
        $bits = $user->bits()->where('team_id', $team->id)->get();
        $folders = $user->folders()->where('team_id', $team->id)->get();

        foreach($files as $file) {
            $file->user_id = $team->user_id;
            $file->owner_id = $team->user_id;

            $file->triggerEvent('teammate_removed');
            $file->save();
        }

        foreach($bits as $bit) {
            $bit->user_id = $team->user_id;
            $bit->owner_id = $team->user_id;

            $bit->triggerEvent('teammate_removed');
            $bit->save();
        }

        //Add all top-level folders to a generated folder of the owner of the team
        $transfer_folder = Folder::create([
            'user_id' => $team->user_id,
            'owner_id' =>$team->user_id,
            'team_id' => $team->id,
            'title'   => $user->name
        ]);

        foreach($folders as $folder) {
            $folder->user_id = $team->user_id;
            $folder->owner_id = $team->user_id;
            $folder->folder_id = $transfer_folder->id;

            $folder->triggerEvent('teammate_removed');
            $folder->save();
        }

        //Remove shares with the owner of the team
        Share::where('team_id',$team->id)
            ->where('user_id',$team->user_id)
            ->where('created_by_id',$user->id)
            ->delete();

        //Transfer all other shares to the owner of the team
        Share::where('team_id', $team->id)
            ->where('created_by_id', $user->id)
            ->update(['created_by_id' => $user->id]);

        //Remove unused shares (share_folders will cascade delete)
        Share::where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->delete();

        //Finally, remove user from team, roles, and corresponding invitations
        $team->users()->detach($user->id);
        $user->roles()->newPivotStatement()
            ->where('user_team_roles.team_id', $team->id)
            ->where('user_team_roles.user_id', $user->id)
            ->delete();

        $user->invitations()->where('team_id', $team->id)->delete();
        $team->invitations()->where('contact', $user->email)->orWhere('contact', $user->phone)->delete();
    }
}