<?php

namespace App\Traits;

use App\Models\Tag;
use Illuminate\Http\Request;

trait TagsResources{
    
    public function tag($id,Request $request){
        $type = $this->get_taggable_type();
        $tags = [];
        
        Tag::where('taggable_id',$id)
            ->where('taggable_type',$type)
            ->delete();
            
        if ($request->has('tags') && is_array($request->tags)) {
            $tags = collect($request->tags)
                ->map(function($tag) use($id,$type){
                    return new Tag([
                        'taggable_id' => $id,
                        'taggable_type' => $type,
                        'text'  => $tag
                    ]);
                });
            Tag::insert($tags->toArray());
        }
        
        return $tags;
    }
    
    /**
     * Override this method in controllers to return the taggable type
     * as defined in the polymorphic mappings
     */ 
    protected function get_taggable_type(){
        return '';
    }
}