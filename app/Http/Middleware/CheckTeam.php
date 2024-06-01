<?php

namespace App\Http\Middleware;

use App\Policies\BasePolicy;
use Closure;
use Illuminate\Support\Facades\Auth;

class CheckTeam {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next) {
        $user = Auth::user();

        if ($request->hasHeader(BasePolicy::ADMIN_API_HEADER) && $user->superuser){
            return $next($request);
        }
        $team = $request->getTeam();
        
        if ($team->suspended) {
            return response()->json([
                'error'   => 'TeamSuspendedException',
                'message' => __('teams.suspended')
            ],403);
        }

        if (!$user->isInTeam($team->id)) {
            return response()->json([
                'error'   => 'TeamMembershipException',
                'message' => __('teams.not_member')
            ], 403);
        }

        return $next($request);
    }
}
