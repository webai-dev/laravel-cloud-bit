<?php

namespace App\Indexing\Documents;
use App\Models\Share;

/**
 * Represents a document in a specific elasticsearch index
 * Subclasses should further specialize the id generation and provide explicit properties
 */ 
class Document{
    
    public function __construct($props = []){
        foreach ($props as $key => $prop) {
            $this->{$key} = $prop;
        }
    }

    public function getId(){
        return spl_object_hash($this);
    }
}