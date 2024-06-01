<?php

namespace App\Traits;

use DB;

trait AddsLockedScope{
    
    public function scopeWithLocked($query,$user_id){
        $table = $this->getTable();
        $type = $this->getType();

        $query->addSelect('*')->leftJoin(DB::raw(
            "(SELECT lockable_id,COUNT(*) > 0 AS locked
                FROM locks WHERE user_id=$user_id
                AND lockable_type = '$type'
                GROUP BY lockable_id
             ) AS lockables"),'lockables.lockable_id','=',$table.'.id'
        )
        ->addSelect(DB::raw("locked as is_locked"));
    }
}