<?php

namespace App\Tracing\Observers;

use App\Models\File;
use App\Tracing\Facades\Activity;

class FileObserver{
  
  public function uploaded(File $item){
    $item->trace('upload');
    Activity::major($item);
    $item->cleanTraces();
  }
  
  public function copied(File $item){
    $item->trace('copy');
    Activity::major($item);
    $item->cleanTraces();
  }
  
  public function updating(File $item){
    $previous_state = File::find($item->id);
    Activity::major($item,$previous_state);
    $item->cleanTraces();
  }
  
  public function opened(File $item){
    $item->trace('download');
    Activity::minor($item);
    $item->cleanTraces();
  }

  public function teammate_removed(File $item) {
    $item->trace('teammate_removed');
    $previous_state = File::find($item->id);

    Activity::major($item, $previous_state);
    $item->cleanTraces();
  }
}