<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Util\JWT;
use Closure;
use Illuminate\Support\Facades\Auth;

class CheckIntegration {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next) {
        list($integration,$payload) = JWT::getIntegrationPayload($request->bearerToken());

        if ($integration != null){
            $user = $integration->user;
            $user->integration_url = $integration->url;
            Auth::setUser($user);
        }
        return $next($request);
    }
}
