<?php

namespace App\Sharing;

use App\Models\Bits\Bit;
use App\Models\Folder;
use App\Models\File;

class ShareableFactory {

    /**
     * Finds a shareable item by id
     * @param $type
     * @param $id
     * @return Shareable
     */
    public static function find($type,$id){
        switch($type){
            case "folder":
                return Folder::findOrFail($id);
            case "bit":
                return Bit::findOrFail($id);
            case "file":
                return File::findOrFail($id);
            default:
                abort(422,"Invalid shareable type");
        }
    }
    
    public static function query($type){
        switch($type){
            case "folder":
                return Folder::query();
            case "bit":
                return Bit::query();
            case "file":
                return File::query();
            default:
                abort(422,"Invalid shareable_type");
        }        
    }
}