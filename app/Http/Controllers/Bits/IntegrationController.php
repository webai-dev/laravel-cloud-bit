<?php

namespace App\Http\Controllers\Bits;

use App\Http\Controllers\Controller;
use App\Models\Bits\Bit;
use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class IntegrationController extends Controller {

    public function __construct(Request $request) {
        JWTAuth::setRequest($request);
    }

    /**
     * Returns the bit of the given type based on the jwt claims
     * @param $type
     * @return Bit
     */
    protected function findBit($type) {
        $claims = $this->getClaims($type);
        return Bit::where('id', $claims->get('jti'))
            ->where('type_id', $type->id)
            ->firstOrFail();
    }

    protected function findUser($type) {
        $claims = $this->getClaims($type);
        return User::where('id', $claims->get('sub'))
            ->firstOrFail();
    }

    protected function getClaims($type) {
        JWTAuth::getJWTProvider()->setSecret($type->jwt_key);

        $token = JWTAuth::getToken();

        return JWTAuth::decode($token);
    }
}
