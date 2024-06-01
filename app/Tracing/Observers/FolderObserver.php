<?php

namespace App\Tracing\Observers;

use App\Models\Folder;
use App\Tracing\Facades\Activity;

class FolderObserver{
  public function opened(Folder $item){
    $item->trace('open');
    Activity::minor($item);
    $item->cleanTraces();
  }

  public function teammate_removed(Folder $item) {
    $item->trace('teammate_removed');
    $previous_state = Folder::find($item->id);

    Activity::major($item, $previous_state);
    $item->cleanTraces();
  }
}