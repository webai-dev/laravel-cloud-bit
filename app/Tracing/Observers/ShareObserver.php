<?php

namespace App\Tracing\Observers;

use App\Models\Share;
use App\Tracing\Facades\Activity;

class ShareObserver
{
  public function created(Share $new){
    $sharable = $new->shareable;
    $sharable->trace('share_add',self::shareMetadata($new));
    Activity::major($sharable);
    $sharable->cleanTraces();
  }
  
  public function updating(Share $new){
    $sharable = $new->shareable;
    $old = Share::find($new->id);
    $sharable->trace('share_edit',self::shareMetadata($new),Activity::generateChanges($new,$old,false));
    Activity::major($sharable);
    $sharable->cleanTraces();
  }
  
  public function deleting(Share $new){
    $sharable = $new->shareable;
    $sharable->trace('share_delete',self::shareMetadata($new));
    Activity::major($sharable);
    $sharable->cleanTraces();
  }
  
  private static function shareMetadata($new){
    return [
      'share_id'  => $new->id,
      'team_id'   => $new->team_id,
      'user_id'   => $new->user_id,
      'edit'      => $new->edit,
      'share'     => $new->share
    ];
  }
}