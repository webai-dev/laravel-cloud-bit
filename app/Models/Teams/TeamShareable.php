<?php

namespace App\Models\Teams;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Tracing\Traits\Traceable;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeamShareable extends Model
{

    protected $table = 'team_shareables';
    protected $guarded = ['created_at','updated_at','id'];

    public function shareable(){
        return $this->morphTo();
    }
}
