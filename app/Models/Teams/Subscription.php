<?php

namespace App\Models\Teams;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Subscription
 * @package App\Models\Teams
 * @property int id
 * @property int team_id
 * @property Team team
 * @property string code The stripe code of the subscription
 * @property string plan_code The stripe code of the plan
 * @property int storage The subscription's storage
 * @property boolean active Whether the subscription is still active
 * @property string type The subscription type (storage,main)
 */
class Subscription extends Model {
    protected $fillable = ['code', 'type', 'plan_code', 'storage', 'status'];

    public function team() {
        return $this->belongsTo(Team::class);
    }

}
