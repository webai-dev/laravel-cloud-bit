<?php

namespace App\Indexing\Documents;

/**
 * Represents a document that indexes a shareable entity.
 * A shareable entity is uniquely identified by the combination of its type,
 * shareable id, the team and the user it belongs to.
 */ 
class ShareableDocument extends Document{
    
    public $title;
    public $content;
    public $path;
    public $shareable_type;
    public $shareable_id;
    public $team_id;
    public $user_id;
    public $owner_id;
    public $last_modified;
    public $type_meta;
    public $data;
    
    public $tags = [];
    public $shared_with = [];
    public $share_id = null;
    
    public function getId(){
        $id = [$this->shareable_type,$this->shareable_id,$this->team_id,$this->user_id];
        return hash('sha256',implode("_",$id)); //Underscores are added so e.g. 11+2 is not the same as 1+12
    }
}