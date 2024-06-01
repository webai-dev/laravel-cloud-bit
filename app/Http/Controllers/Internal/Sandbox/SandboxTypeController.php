<?php

namespace App\Http\Controllers\Internal\Sandbox;

use App\Http\Controllers\Controller;
use App\Models\Bits\Type;
use App\Models\Teams\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SandboxTypeController extends Controller {

    public function store(Request $request) {

        $this->validate($request, [
            'name'        => 'required|string|max:255',
            'base_url'    => 'required|url',
            'display_url' => 'url',
            'jwt_key'     => 'string',
            'width'       => 'required|integer',
            'height'      => 'required|integer',
        ]);

        $type = new Type($request->only(['name', 'base_url', 'width', 'height']));
        $type->user_id = Auth::id();

        if($request->input('jwt_key')){
            $type->jwt_key = $request->input('jwt_key');
        } else {
            $type->jwt_key = Str::random(64);
        }

        $type->display_url = $request->input('display_url', $request->input('base_url'));
        $type->public = 0;
        $type->draft = 1;
        $type->save();

        $team = $request->getTeam();
        $team->bitTypes()->attach($type);

        return[
            'id' => $type->id,
            'name' => $type->name,
            'width' => $type->width,
            'height' => $type->height,
            'base_url' => $type->base_url,
            'display_url' => $type->display_url,
            'jwt_key' => $type->jwt_key
        ];
    }

    public function update(Type $type, Request $request){
        if(!$type->draft){
            abort(422 ,__('bits.draft_warning'));
        }

        $type->fill($request->all());
        $type->save();
        return $type;
    }

    public function destroy($type_id){
        $type = Type::find($type_id);
        if(!$type->draft){
            abort(422 ,__('bits.draft_warning'));
        }
        $bits = $type->bits;
        foreach ($bits as $bit){
            $bit->forceDelete();
        }
        $type->forceDelete();
        return $type;
    }

    public function index(Request $request){
        $team = $request->getTeam();

        $types = $team->bitTypes()->with('bits')->where('bit_types.draft',true)->get();

        return $types->map(function($type){
            return [
                'id' => $type->id,
                'name' =>$type->name,
                'base_url' => $type->base_url,
                'display_url' => $type->display_url,
                'width' => $type->width,
                'jwt_key' => $type->jwt_key,
                'height' => $type->height,
                'instances' => $type->bits
            ];
        });
    }

}