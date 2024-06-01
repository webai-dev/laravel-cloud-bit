<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\Teams\Team;
use App\Models\Teams\Invitation;
use App\Models\Bits\Bit;
use App\Tracing\Traits\Traceable;

/**
 * Class Role
 * @package App\Models
 * @property int id
 * @property string name
 * @property string label
 */
class Role extends Authenticatable
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'label'
    ];
    public $timestamps = false;

    public function users(){
        return $this->belongsToMany(User::class,'user_team_roles');
    }
}
