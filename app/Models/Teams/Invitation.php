<?php

namespace App\Models\Teams;

use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\Tracing\Traits\Traceable;
use Illuminate\Notifications\Notifiable;

/**
 * Class Invitation
 * @package App\Models\Teams
 * @property int id
 * @property int user_id
 * @property int team_id
 * @property int role_id
 * @property string contact
 * @property string status
 * @property Team team
 * @property Role role
 * @property Carbon created_at
 * @property Carbon updated_at
 */
class Invitation extends Model
{
    use Traceable,Notifiable;
    
    public static $exclude_default_events = ['created','updating','deleting'];
    protected $table = 'team_invitations';
    protected $fillable = ['user_id','team_id','role_id','contact'];
    protected $hidden = ['team_id','user_id'];

    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED= 'accepted';
    const STATUS_REJECTED= 'rejected';
    
    public function getTypeAttribute(){
        if (strpos($this->contact,"@") === false) {
            return "phone";
        }else{
            return "email";
        }
    }
    
    public function team(){
        return $this->belongsTo(Team::class);
    }
    
    public function user(){
        return $this->belongsTo('App\Models\User');
    }
    
    public function routeNotificationForMail(){
        return $this->contact;
    }
    
    public function routeNotificationForTwilio(){
        return $this->contact;
    }

    public function role(){
        return $this->belongsTo(Role::class);
    }
    
}
