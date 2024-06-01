<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Tracing\Traits\Traceable;

class Lock extends Model
{
    use Traceable;
    protected $fillable = ['lockable_type','lockable_id','user_id','team_id'];
    public static $exclude_default_events = ['created','updating','deleting'];
    
    public function lockable(){
        return $this->morphTo();
    }
}
