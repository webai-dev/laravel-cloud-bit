<?php

namespace App\Http\Controllers\Internal\Auth;

use App\Http\Controllers\Controller;
use App\Models\UserMagicToken;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;

class AuthController extends Controller {
    public function link(Request $request, JWTAuth $auth) {
        $this->validate($request, [
            'token' => 'required'
        ]);

        /** @var UserMagicToken $token */
        $token = UserMagicToken::query()
            ->where('token', $request->input('token'))
            ->first();

        if ($token == null) {
            return response()->json([
                'message' => __('auth.invalid_token')
            ], 400);
        }

        if ($token->isExpired()) {
            return response()->json([
                'message' => __('auth.expired_token')
            ], 400);
        }

        $user = $token->user;
        $token->delete();

        return response()->json([
            'user'  => $user,
            'token' => $auth->fromUser($user)
        ]);
    }
}
