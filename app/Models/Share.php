<?php

namespace App\Models;

use App\Models\Teams\Team;
use App\Sharing\Shareable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use App\Tracing\Traits\Traceable;
use App\Indexing\Documentable;

/**
 * Class Share
 * @package App\Models
 * @property int id
 * @property int shareable_id
 * @property int user_id
 * @property int team_id
 * @property int created_by_id
 * @property string shareable_type
 * @property string rename
 * @property boolean edit
 * @property boolean share
 * @property boolean moved @deprecated
 * @property Shareable shareable
 * @property Collection folders
 * @property User recipient
 * @property User creator
 * @property Team team
 *
 */
class Share extends Model implements Documentable
{
    use Traceable;

    protected $fillable = ['shareable_id','shareable_type','edit','share',
        'user_id','team_id','created_by_id'];
    public static $exclude_default_events = ['created','updating','deleting'];

    public function folders(){
        return $this->belongsToMany(Folder::class,"share_folders");
    }

    public function shareable(){
        return $this->morphTo();
    }

    public function team(){
        return $this->belongsTo('App\Models\Teams\Team');
    }

    public function recipient(){
        return $this->belongsTo('App\Models\User','user_id');
    }

    public function creator(){
        return $this->belongsTo('App\Models\User','created_by_id');
    }

    public function shortcuts(){
        return $this->hasMany('App\Models\Shortcut');
    }

    public function toDocumentArray($from_shareable = null){
        $share = $this;

        $indexes   = [];
        $shareable = $from_shareable ?: $share->shareable;

        if ($share->rename != null) {
            $shareable->setRenamedTitle($share->rename);
        }

        $doc = $shareable->toDocument();

        $doc->user_id = $share->user_id;
        $doc->share_id = $share->id;
        $doc->folder_id = $shareable->folder_id;

        $indexes[] = $doc;

        return $indexes;
    }

    public function toDocument(){
        return $this->shareable->toDocument();
    }

    /**
     * Compares the permissions of this share with another,
     * and returns 0 if they are equivalent, -1 if this share has less permissions, and 1 if it has more
     * @param Share $other
     * @return int
     */
    public function comparePermissions(Share $other){
        if ($this->share && $other->share){
            return 0;
        }
        if (!$this->share && $other->share){
            return -1;
        }
        if ($this->share && !$other->share){
            return 1;
        }

        if ($this->edit && $other->edit){
            return 0;
        }
        if (!$this->edit && $other->edit){
            return -1;
        }
        return 1;
    }
}
