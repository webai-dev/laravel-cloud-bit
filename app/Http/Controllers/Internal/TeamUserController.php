<?php

namespace App\Http\Controllers\Internal;

use App\Models\User;
use App\Models\Teams\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\TeamUserRemoved;
use App\Http\Controllers\Controller;

class TeamUserController extends Controller{

    public function index(Team $team,Request $request){
        $this->authorize('view',$team);
        
        return $team->users()
            ->leftJoin('user_team_roles',function(JoinClause $join) use($team){
                $join->on('user_team_roles.user_id','=','users.id')
                     ->where('user_team_roles.team_id','=',$team->id);
            })
            ->when($request->has('search'),function(Builder $query) use($request){
                return $query->where('email','LIKE','%'.$request->input('search').'%');
            })
            ->orderBy('name','ASC')
            ->select('users.*','team_users.developer','role_id')
            ->get()
            ->map(function($user) use($team){
                if($team->user_id == $user->id){
                    $user->is_owner = true;
                }
                return $user;
            });
    }

    public function update(Team $team,User $user,Request $request){
        $this->authorize('update',$team);
        $this->validate($request,[
            'role_id' => 'required|integer|exists:roles,id'
        ]);

        if (Auth::id() == $user->id) {
            abort(400,__('teams.self_role_change'));
        }
        if ($team->user_id == $user->id){
            abort(400,__('teams.owner_role_change'));
        }

        $user->roles()->newPivotStatement()
            ->where('user_team_roles.team_id',$team->id)
            ->where('user_team_roles.user_id',$user->id)
            ->delete();

        $user->roles()->attach($request->input('role_id'),['team_id' => $team->id]);
        
        return $user;
    }

    public function ban(Team $team, User $user, Request $request){
        $this->authorize('update',$team);

        if (Auth::id() == $user->id) {
            abort(400,__('teams.self_ban'));
        }
        if ($team->user_id == $user->id){
            abort(400,__('teams.owner_ban'));
        }

        $team->users()->newPivotStatement()
            ->where('team_users.user_id',$user->id)
            ->where('team_users.team_id',$team->id)
            ->update(['banned' => $request->input('banned', true)]);

        return $user;
    }

    public function developer(Team $team, User $user, Request $request){
        $this->authorize('update',$team);
        $team->users()->newPivotStatement()
             ->where('team_users.user_id',$user->id)
             ->where('team_users.team_id',$team->id)
             ->update(['developer' => $request->input('developer', true)]);

        return $user;
    }
    
    public function remove(Team $team,User $user){
        if (Auth::id() != $user->id) {
            $this->authorize('update',$team);
        }

        if($user->id == $team->user_id){
            abort(400,__('teams.owner_removal'));
        }

        event(new TeamUserRemoved($user,$team));
        
        return response()->json([
            'message' => __('teams.user_removed',['user'=>$user->name]) 
        ]);
    }

}
