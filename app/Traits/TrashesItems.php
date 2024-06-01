<?php

namespace App\Traits;

use App\Sharing\Shareable;
use Illuminate\Http\Request;

trait TrashesItems{

    public function trash($id,Request $request){
        /** @var Shareable $item */
        $item = $this->find($id,$request);
        
        $this->authorize('delete',$item);
            
        $item->unshare();
        $item->locks()->delete();
        $item->delete();
        
        return $item;
    }
}