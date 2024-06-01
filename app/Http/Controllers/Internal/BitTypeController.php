<?php

namespace App\Http\Controllers\Internal;

use App\Models\Bits\Type;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;

class BitTypeController extends Controller {

    const RECENT_LIMIT = 4;

    public function index(Request $request) {
        $team = $request->getTeam();
        $types = $team->bitTypes()
            ->when($request->has('enabled'), function (Builder $query) use ($request) {
                $query->where('bit_type_teams.enabled', $request->input('enabled'));
            })
            ->where('public',true)
            ->withPivot('enabled')
            ->get();

        return $types;
    }

    public function recent(Request $request) {
        $types = Type::query()
            ->join(DB::raw('(
                SELECT MAX(created_at) AS last_created, type_id
                FROM bits 
                WHERE user_id = '.Auth::id().'
                GROUP BY type_id
            ) AS last_creations'),
                'last_creations.type_id','=','bit_types.id')
            ->select('bit_types.id','name','icon','color')
            ->join('bit_type_teams','bit_type_teams.type_id','=','bit_types.id')
            ->where('bit_type_teams.team_id',$request->getTeam()->id)
            ->where('bit_type_teams.enabled',true)
            ->orderBy('last_created', 'DESC')
            ->limit(self::RECENT_LIMIT)
            ->get();

        return $types;
    }

    public function toggle($id, Request $request) {
        $team = $request->getTeam();
        $type = $team->bitTypes()
            ->where('bit_types.id', $id)
            ->withPivot('enabled')
            ->firstOrFail();

        $enabled = $type->pivot->enabled;

        $team->bitTypes()->updateExistingPivot($id, ['enabled' => !$enabled]);

        $type->pivot->enabled = $enabled;

        return $type;
    }

    public function store(Request $request) {
        $this->validate($request, [
            'name'        => 'required|string|max:255|unique:bit_types,name',
            'base_url'    => 'required|url',
            'display_url' => 'url',
            'width'       => 'required|integer|max:12|min:2',
            'height'      => 'required|integer|min:100',
        ]);

        $type = new Type($request->only(['name', 'base_url', 'width', 'height']));
        $type->user_id = Auth::id();
        $type->jwt_key = Str::random(64);
        $type->display_url = $request->input('display_url', $request->input('base_url'));
        $type->public = 0;
        $type->draft = 1;
        $type->save();

        return $type;
    }

    public function publish(Type $type, Request $request) {
        $this->validate($request, [
            'public'  => 'required|in:0,1',
            'teams'   => 'required_if:public,0|array|min:1',
            'teams.*' => 'integer|exists:teams,id',
        ]);

        if ($type->reviewed == 0) {
            abort(400, __('bits.type_not_reviewed'));
        }
        if ($type->draft == 0) {
            abort(400, __('bits.type_already_published'));
        }

        $type->draft = 0;

        if ($request->input('public') == 1) {
            $type->public = 1;
        } else {
            $type->teams()->attach($request->input('teams'));
        }

        $type->save();

        return $type;
    }
}
