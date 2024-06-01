<?php

namespace App\Traits;

trait InteractsWithShareables{
    use FindsItems,LocksItems,MovesItems,TrashesItems;
}