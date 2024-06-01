<?php

namespace App\Indexing;

use App\Indexing\Jobs\IndexCreatedItem;
use App\Indexing\Jobs\IndexDeletedItem;

use Illuminate\Database\Eloquent\Model;

trait Indexable{
    
    public static $update_index = true;
    
    public static function bootIndexable(){
        
        static::saved(function(Model $item) {
            if (self::$update_index) {
                dispatch(new IndexCreatedItem($item));
            }
        });
        
        static::deleting(function(Model $item) {
            dispatch(new IndexDeletedItem($item));
        });
    }
}