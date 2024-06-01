<?php
namespace App\Sharing;

use App\Models\Folder;
use App\Exceptions\PermissionException;

class Permissions{
    
    public $share  = 0;
    public $edit   = 0;
    public $path   = [];
    
    /**
     * Creates a permission object from given flags
     * 
     * Invalid flags will result in no permissions
     * 
     * @param mixed $flags Either a 3-char string (e.x. "101") 
     * or an array with keys "share","edit","delete"
     *
     * @return Permissions The parsed permissions object
     */ 
    public static function parse($flags){
        $permissions = new Permissions();
        
        if (is_array($flags)) {
            $permissions->share  = isset($flags['share']) ? $flags['share'] : 0;
            $permissions->edit   = isset($flags['edit']) ? $flags['edit'] : 0;
        }elseif(gettype($flags) == 'string' && strlen($flags) == 3){
            $permissions->share  = $flags[0];
            $permissions->edit   = $flags[1];
        }elseif(gettype($flags) == 'object'){
            $permissions->share  = $flags->share;
            $permissions->edit   = $flags->edit;
        }
        return $permissions;
    }
    
    /**
     * Finds the permissions that correspond to a shareable model for a user.
     * The owner of an item always has share permissions
     *       
     * @param Shareable $shareable A Shareable model
     * @param int   $user_id The user to find the share for
     * 
     * @return Permissions $permissions The permission object for this user, null if no permissions exist
     */ 
    public static function of(Shareable $shareable,$user_id){
        
        $current = $shareable;
        $team_id = $shareable->team_id;
        
        do {
            
            //The owner of a folder has all permissions on all sub-folders and contents
            if ($current->user_id == $user_id) {
                return self::parse("111");
            }
            
            $share = $current->shares()
                               ->where('user_id',$user_id)
                               ->orWhere(function($share) use($team_id,$user_id){
                                   $share->where('team_id',$team_id)
                                         ->whereNull('user_id');
                               })
                               ->first();
            
            if ($share == null) {
                //A top-level item that isn't shared can only be viewed by its creator
                if ($current->folder_id == null){
                    return null;
                }
                
                //If the item isn't shared, maybe its parent is
                $current = $current->folder;
            }else{
                return self::parse($current);
            }
            
        } while ($current != null);
        
        
        return self::parse("111");
    }

    /**
     * Checks the permissions that correspond to a shareable model
     * @deprecated Use $shareable->hasPermissionFor instead for checking permissions and $shareable->getPathFor to get the path
     *
     * @param Shareable $shareable A Shareable model
     * @param int   $team_id The team to find the share for
     * @param int   $user_id The user to find the share for
     * @param string $permission The permission to check
     * @throws PermissionException If a suitable permission isn't found, an exception is thrown
     *
     * @return array $path The full path to the shareable from the user's root
     */
    public static function check(Shareable $shareable,$team_id,$user_id,$permission){

        $path = [];
        $current = $shareable;

        do {
            $path[] = ['id'=>$current->id,'title'=>$current->title];

            $share = $current->shares()
                ->where('user_id',$user_id)
                ->orWhere(function($share) use($team_id,$user_id){
                    $share->where('team_id',$team_id)
                        ->whereNull('user_id');
                })
                ->first();

            if ($share == null) {
                //If the item isn't shared and has no parent, only the creator can view it
                if ($current->folder_id == null && $current->user_id != $user_id){
                    throw new PermissionException($current->getType(),$permission);
                }

                //If the item isn't shared, maybe its parent is
                $current = $current->folder;
            }else{
                //If the item is shared, check permission
                if($permission != 'view' && $share->{$permission} == 0)
                    throw new PermissionException($current->getType(),$permission);

                //Continue moving up the path for the current user
                $current = $share->folders()
                    ->where('share_folders.user_id','=',$user_id)
                    ->first();

            }

        } while ($current != null);

        return array_reverse($path);
    }

    /**
     * Checks what permissions does this object cover for another one
     *
     * A permission is considered covered, if either the two permissions
     * are the same, or if the covering object has the permission
     *
     * @param Permissions $other The permission object to test against
     *
     * @return array An array containing booleans that are true for the permissions that are covered
     */
    public function cover(Permissions $other){
        return [
            'share' => $this->share == $other->share  || $this->share == 1,
            'edit' => $this->edit == $other->edit || $this->edit == 1
        ];
    }

    public function __toString(){
        return json_encode([
            'share'=>$this->share,
            'edit'=>$this->edit
        ]);
    }
}