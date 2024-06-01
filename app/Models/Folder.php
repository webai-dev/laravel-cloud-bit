<?php

namespace App\Models;

use App\Sharing\Visitors\ShareableVisitor;
use Illuminate\Database\Eloquent\Collection;
use App\Sharing\Shareable;

/**
 * Class Folder
 * @package App\Models
 * @property Collection folders
 * @property Collection bits
 * @property Collection files
 * @property int shared_children_count
 * @property-write bool is_shortcut
 * @property-write int shortcut_id
 * @property-write int share_id
 */
class Folder extends Shareable
{
    
    protected $fillable = ['title','folder_id','team_id','user_id','owner_id'];
    protected $type = 'folder';
    protected $observables = ['opened', 'teammate_removed'];
    
    public function folders(){
        return $this->hasMany(Folder::class);
    }
    
    public function files(){
        return $this->hasMany(File::class);
    }
    
    public function bits(){
        return $this->hasMany('App\Models\Bits\Bit');
    }
    
    public function getType(){
        return $this->type;
    }
    
    public function team(){
        return $this->belongsTo('App\Models\Teams\Team');
    }

    /**
     * Returns an array of all folders that are descendants of this folder,
     * and are first-level shared
     * @return array
     */
    public function getFirstSharedDescendants(){
        $descendants = [];
        /** @var Folder $folder */
        foreach ($this->folders as $folder) {
            if ($folder->is_shared){
                $descendants[] = $folder;
            }else{
                $descendants = array_merge($descendants,$folder->getFirstSharedDescendants());
            }
        }
        return $descendants;
    }

    public function isDescendantOf(Folder $other){
        foreach ($other->folders as $folder) {
            if ($this->isDescendantOf($folder) || $this->id === $folder->id){
                return true;
            }
        }
        return false;
    }

    public function transferDescendants($from_id,$to_id){
        $this->bits()->where('user_id',$from_id)->update(['user_id' => $to_id]);

        $this->files()->where('user_id',$from_id)->update(['user_id' => $to_id]);

        $this->folders()->where('user_id',$from_id)->update(['user_id' => $to_id]);

        foreach ($this->folders as $folder) {
            $folder->transferDescendants($from_id,$to_id);
        }
    }

    public function unlockDescendants($user_id){
        Lock::query()
            ->where([
                'user_id' => $user_id,
                'lockable_type' => 'bit'
            ])
            ->whereIn('lockable_id',$this->bits()->pluck('id')->toArray())
            ->delete();
        Lock::query()
            ->where([
                'user_id' => $user_id,
                'lockable_type' => 'file'
            ])
            ->whereIn('lockable_id',$this->files()->pluck('id')->toArray())
            ->delete();
        Lock::query()
            ->where([
                'user_id' => $user_id,
                'lockable_type' => 'folder'
            ])
            ->whereIn('lockable_id',$this->folders()->pluck('id')->toArray())
            ->delete();

        foreach ($this->folders as $folder) {
            $folder->locks()->where('user_id',$user_id)->delete();
            $folder->unlockDescendants($user_id);
        }
    }

    public function accept(ShareableVisitor $visitor) {
        $visitor->visitFolder($this);
    }
}
