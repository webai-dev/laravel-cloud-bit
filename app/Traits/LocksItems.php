<?php

namespace App\Traits;

use Illuminate\Http\Request;

use App\Models\Lock;
use Illuminate\Support\Facades\Auth;

trait LocksItems{
    
    public function toggle_locked($id,Request $request){

        $item = $this->find($id,$request);
        $this->authorize('view',$item);

        $fields = [
            'lockable_type' => $item->getType(),
            'lockable_id'   => $id,
            'user_id'       => Auth::id(),
            'team_id'       => $item->team_id
        ];
        
        $lock = Lock::where($fields)->first();
        
        if($lock){
            $lock->delete();
            $item->is_locked = 0;
        }else{
            Lock::create($fields);
            $item->is_locked = 1;
        }
        
        return $item;
    }
    
    public function show_locked(Request $request){
        $this->validate($request,['team_id'=>'required']);
        $model = $this->findModel($request);
        
        $locked = $model::whereHas('locks',function($query) use($request) {
            $query->where('user_id',Auth::id())
                  ->where('team_id',$request->team_id);
        })->withCount('shares')->get();
        
        return $locked;
    }
}