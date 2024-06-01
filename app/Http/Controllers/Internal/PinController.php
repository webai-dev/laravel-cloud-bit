<?php

namespace App\Http\Controllers\Internal;

use App\Models\Pins\Pin;
use App\Models\Pins\Type;
use App\Models\Favourite;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Auth;

class PinController extends Controller
{
    public function types(){
        return Type::all(); 
    }

    public function index(Request $request){
        $pins = Pin::where('team_id',$request->team_id)
        ->with(['user','content'])
        ->when($request->favourites == 1,function(Builder $pins){
            $pins->where('favourite',1);
        })
        ->orderBy('created_at','DESC')
        ->paginate(24);
        
        $favourite_count = Pin::where('team_id',$request->team_id)
                              ->where('favourite',1)
                              ->count();
                              
        return compact('pins','favourite_count');
    }

    public function store(Request $request){
        $this->validate($request,[
            'team_id' => 'required',
            'type_id' => 'required|exists:pin_types,id',
            'content' => 'required'
        ]);

        $this->authorize('create',Pin::class);

        $type = Type::find($request->type_id);
        
        $class = Pin::MORPH_MAP[$type->label];
        
        $content = new $class($request->input('content',[]));
        $this->validate($request,$content->validations);
        
        $content->save();
        
        $pin = new Pin([
            'user_id' => Auth::id(),
            'team_id' => $request->team_id,
            'type_id' => $request->type_id,
            'content_id' => $content->id,
            'content_type' => $type->label
        ]);
        $pin->save();
        
        return $pin;
    }

    public function favourite($id){
        $user = Auth::user();
        $data = [
            'user_id' => $user->id,
            'favourite_id' => $id,
            'favourite_type' => 'pins'
        ];
        $favourite = Favourite::where($data)
                              ->first();
                              
        if($favourite != null){
            $favourite->delete();
        }else{
            Favourite::create($data);
        }
        
        return response()->json(['message'=>__('pins.favourites_updated')]);
    }
    
    public function show($id,Request $request){
        $pin = Pin::where('team_id',$request->team_id)
                ->where('id',$id)
                ->with('content')->firstOrFail();
                
        return $pin;
    }

    public function update($id,Request $request){
        $this->authorize('update',Pin::class);

        $pin = $this->show($id,$request);
        
        $content = $pin->content;
        $this->validate($request,$content->validations);
        $content->update($request->input('content',[]));
        return $pin;
    }

    public function destroy($id,Request $request){
        $this->authorize('delete',Pin::class);

        $pin = $this->show($id,$request);
        $pin->content->delete();
        $pin->delete();
        return $pin;
    }
}
