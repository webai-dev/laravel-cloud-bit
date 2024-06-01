<?php

namespace App\Services\Bits;

use App\Models\Bits\Bit;

use App\Models\User;

use JWTFactory;
use JWTAuth;

interface BitService{

    /**
     * Response timeout in seconds
     * 
     * Is set to be the same as the token expiry time, 
     * so if the token expires, the request times out
     */
    const TIMEOUT = 2 * 60;

    public function getToken(Bit $bit,User $user);

    public function fetch(Bit $bit);

    public function create(Bit $bit);

    public function update(Bit $bit,$content);

    public function remove(Bit $bit);
}