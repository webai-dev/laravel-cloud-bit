<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckDeveloper {
    public function handle(Request $request, Closure $next) {
        $team = $request->getTeam();
        $user = $team->users()
                     ->withPivot('developer')
                     ->where('id',Auth::id())
                     ->firstOrFail();

        if (!$user->pivot->developer){
            return response()->json([
                'error' => 'UserPermissionException',
                'message' => __('auth.not_a_developer')
            ],403);
        }

        return $next($request);
    }
}