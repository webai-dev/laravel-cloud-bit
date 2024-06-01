<?php

namespace App\Http\Controllers\Internal;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Sharing\ShareableFactory;

class ActivityController extends Controller
{
    public function index($target_type,$target_id,Request $request) {
        $this->validate($request,[
            'major' => 'integer|in:0,1',
        ]);
        
        //type is folders/bits/files so we remove the trailing 's'
        $type=substr($target_type, 0, -1);
        
        //instantiate target and fail or unauthorize
        $item = ShareableFactory::find($type,$target_id);
        $this->authorize('view', $item);

        $activities = Activity::where('target_id',$target_id)
                        ->where('target_type',$type)
                        ->with(['user'=> function($q){$q->select('id','name','photo');}])
                        ->when(!is_null($request->input('major',null)),function($q) use ($request){
                            $q->where('major',$request->major);
                        })->orderBy('created_at','desc')
                        ->select(['major','action','changes','metadata','user_id','created_at'])->paginate(20);
        return $activities;
    }
}
