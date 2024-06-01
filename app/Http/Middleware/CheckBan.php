<?php

namespace App\Http\Middleware;

use App\Policies\BasePolicy;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckBan {

    public function handle(Request $request, Closure $next) {
        $user = Auth::user();

        if ($user->banned) {
            return response()->json([
                'error'   => 'UserBannedException',
                'message' => __('banned_user'),
            ], 403);
        }

        if ($request->hasHeader(BasePolicy::ADMIN_API_HEADER) && $user->superuser){
            return $next($request);
        }

        $team = $request->getTeam();

        $user = $team->users()
                     ->withPivot('banned')
                     ->where('id',Auth::id())
                     ->firstOrFail();

        if ($user->pivot->banned){
            return response()->json([
                'error' => 'UserBannedException',
                'message' => __('banned_from_team')
            ],403);
        }

        return $next($request);
    }
}
