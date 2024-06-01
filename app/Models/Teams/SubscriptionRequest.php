<?php

namespace App\Models\Teams;

use App\Models\Role;
use Illuminate\Database\Eloquent\Model;

/**
 * Class SubscriptionRequest
 * @package App\Models\Teams
 * @property int id
 * @property int team_id
 * @property Team team
 */
class SubscriptionRequest extends Model
{
    protected $fillable = [
        'name',
        'surname',
        'email',
        'company',
        'company_size',
        'required_storage',
        'custom_bits',
        's3_integration'
    ];
    
    public function team(){
        return $this->belongsTo(Team::class);
    }
    
}
