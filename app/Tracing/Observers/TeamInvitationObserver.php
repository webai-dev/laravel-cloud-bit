<?php

namespace App\Tracing\Observers;

use App\Models\Teams\Invitation;
use App\Tracing\Facades\Activity;

class TeamInvitationObserver
{
  public function created(Invitation $new){
    $team = $new->team;
    $team->trace('invitation_add',self::customMetadata($new));
    Activity::major($team);
    $team->cleanTraces();
  }
  
  public function updating(Invitation $new){
    $team = $new->team;
    $old = Invitation::find($new->id);
    $team->trace('invitation_answer',self::customMetadata($new),Activity::generateChanges($new,$old,false));
    Activity::major($team);
    $team->cleanTraces();
  }
  
  public function deleting(Invitation $new){
    $team = $new->team;
    $team->trace('invitation_delete',self::customMetadata($new));
    Activity::major($team);
    $team->cleanTraces();
  }
  
  private static function customMetadata($new){
    return [
      'invitation_id'   => $new->id,
      'team_id'         => $new->team_id,
      'user_id'         => $new->user_id,
      'contact'         => $new->contact,
    ];
  }
}