<?php

namespace App\Sharing;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

trait HandlesRenaming {

    protected $renamed_title = null;

    public function getTitleAttribute(){
        if (array_key_exists('renamed_title',$this->attributes)
            && $this->attributes['renamed_title'] != null) {
            return $this->attributes['renamed_title'];
        }
        if ($this->renamed_title != null) {
            return $this->renamed_title;
        }
        return $this->attributes['title'];
    }

    public function setTitleAttribute($value){
        $user = Auth::user();
        $this->renamed_title = $value;

        //When running tests or commands, user is null
        if ($user == null) {
            $this->attributes['title'] = $value;
            return;
        }

        $share = $this->getShareFor($user->id);

        // If the item is not directly shared, or the user renaming it is its owner,
        // change the actual title
        if ($share == null || $this->attributes['user_id'] == $user->id) {
            $this->attributes['title'] = $value;
            return;
        }

        //Otherwise, change the renaming of the share
        $share->rename = $value;
        $share->save();
    }

    public function setRenamedTitle($title){
        $this->renamed_title = $title;
    }

    public function scopeWithRenamedTitle($query,$user_id){
        $table = $this->getTable();
        $type = $this->getType();

        $query->addSelect(DB::raw("(
            SELECT rename FROM shares 
            WHERE shareable_id = $table.id
            AND shareable_type = '$type'
            AND user_id = $user_id
            LIMIT 1
        ) AS renamed_title"));
    }

}