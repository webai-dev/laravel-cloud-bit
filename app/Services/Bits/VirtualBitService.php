<?php

namespace App\Services\Bits;

use App\Models\Bits\Bit;
use App\Models\Bits\Type;

use App\Models\User;
use App\Exceptions\BitServiceException;
use App\Exceptions\BitValidationException;
use Illuminate\Support\Facades\Log;

use JWTFactory;
use JWTAuth;

use Tymon\JWTAuth\Token;

class VirtualBitService implements BitService {
    
    protected $key;
    protected $url;
    protected $client;
    protected $type;

    public function setType(Type $type) {
        $this->key = $type->jwt_key;
        $this->url = $type->base_url;

        $this->type = $type;
    }

    protected function createJWT($claims = []){
        JWTAuth::getJWTProvider()->setSecret($this->key);
        
        $payload = JWTFactory::make($claims);
        
        return JWTAuth::encode($payload);
    }

    /**
     * Creates a JWT for a specific bit
     * @param Bit $bit
     * @param User $user The user to use in the bit
     * @return Token
     */
    public function getToken(Bit $bit,User $user){

        return $this->createJWT([
            'sub'    => $user->id,
            'aud'    => $user->name,
            'jti'    => $bit->id,
            'own'    => $bit->user_id,
            'edt'    => $bit->hasPermissionFor('edit', $user->id)
        ]);
    }

    /**
     * Mock function for fetch bit functionality.
     * @param Bit $bit
     * @return mixed
     * @throws BitServiceException
     * @throws BitValidationException
     */
    public function fetch(Bit $bit){
        Log::info('Bit fetched');
    }

    /**
     * Mock function for create bit functionality.
     * @param Bit $bit
     * @throws BitServiceException
     * @throws BitValidationException
     */
    public function create(Bit $bit){
        Log::info('Bit created');
    }

    /**
     * Mock function for update bit functionality.
     * @param Bit $bit
     * @param $content
     * @throws BitServiceException
     * @throws BitValidationException
     */
    public function update(Bit $bit,$content){
        Log::info('Bit updated');
    }

    /**
     * Mock function for delete bit functionality.
     * @param Bit $bit
     * @throws BitServiceException
     * @throws BitValidationException
     */
    public function remove(Bit $bit){
        Log::info('Bit removed');
    }
}