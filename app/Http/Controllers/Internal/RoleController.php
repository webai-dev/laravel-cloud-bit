<?php

namespace App\Http\Controllers\Internal;

use App\Models\Role;
use App\Models\Teams\Team;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class RoleController extends Controller
{
    public function index(){
        return Role::query()->orderBy('name','ASC')->get();
    }

    public function show(Team $team){
        $user = Auth::user();
        if ($user->id == $team->user_id){
            return [
                'name' => 'Owner',
                'label'=> 'owner'
            ];
        }

        $role = $user->roles()->where('team_id',$team->id)->firstOrFail();
        return $role;
    }
}
