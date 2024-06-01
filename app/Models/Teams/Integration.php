<?php

namespace App\Models\Teams;


use Illuminate\Database\Eloquent\Model;
use App\Models\Folder;
use App\Models\User;

/**
 * Class Integration
 * @package App\Models\Teams
 * @property int id
 * @property int team_id
 * @property string secret
 * @property string key
 * @property string name
 * @property string url
 */
class Integration extends Model {

    protected $table = 'team_integrations';
    protected $fillable = ['name', 'url', 'folders', 'user_id'];

    public function getSecretAttribute($value) {
        return decrypt($value);
    }

    public function team(){
        return $this->belongsTo(Team::class);
    }

    public function folders(){
        return $this->belongsToMany(Folder::class, 'integration_folders', 'integration_id', 'folder_id');
    }

    public function user(){
        return $this->belongsTo(User::class);
    }
}
