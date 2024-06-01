<?php
namespace Tests;

use App\Models\Bits\Bit;
use App\Models\Teams\Integration;
use App\Models\User;
use App\Services\Bits\BitServiceImpl;
use Firebase\JWT\JWT;
use Tymon\JWTAuth\Facades\JWTAuth;

trait AttachesJWT {
    /**
     * Uses bearer authorization as a specific user
     * @param User $user
     * @return TestCase
     */
    public function asUser(User $user){
        return $this->withToken(JWTAuth::fromUser($user));
    }

    /**
     * Uses bearer authorization as an external bit
     * @param Bit $bit
     * @param User $user
     * @return TestCase
     */
    public function asBit(Bit $bit,User $user){
        $service = new BitServiceImpl();
        $service->setType($bit->type);
        return $this->withToken($service->getToken($bit,$user));
    }

    /**
     * Uses bearer authorization as an external integration
     * @param Integration $integration
     * @param User $user
     */
    public function asIntegration(Integration $integration,User $user){
        $token = JWT::encode([
            'sub' => $user->apparatus_id
        ],$integration->secret);
        $this->withToken($token);
    }

    /**
     * @param $token
     * @return TestCase
     */
    protected function withToken($token) {
        return $this->withServerVariables([
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'HTTP_ACCEPT' => 'application/json'
        ]);
    }
}