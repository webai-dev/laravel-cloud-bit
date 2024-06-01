<?php

namespace App\Tracing\Observers;

use App\Models\Bits\Bit;
use App\Tracing\Facades\Activity;

class BitObserver{
  public function opened(Bit $item){
    $item->trace('open');
    Activity::minor($item);
    $item->cleanTraces();
  }

  public function teammate_removed(Bit $item) {
    $item->trace('teammate_removed');
    $previous_state = Bit::find($item->id);

    Activity::major($item, $previous_state);
    $item->cleanTraces();
  }
}