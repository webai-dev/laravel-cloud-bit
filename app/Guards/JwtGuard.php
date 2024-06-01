<?php

namespace App\Guards;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\Authenticatable;
use JWTAuth;

class JwtGuard implements Guard{
    
    protected $user_cached = null;
    
    /**
     * Determine if the current user is authenticated.
     *
     * @return bool
     */
    public function check(){
        return $this->user() != null;
    }
    /**
     * Determine if the current user is a guest.
     *
     * @return bool
     */
    public function guest(){
        return !$this->check();
    }
    /**
     * Get the currently authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user(){
        //Use cached instance
        if ($this->user_cached != null) {
            return $this->user_cached;
        }
        
        $this->user_cached = JWTAuth::parseToken()->authenticate();
        return  $this->user_cached; 
    }
    
    /**
     * Get the ID for the currently authenticated user.
     *
     * @return int|null
     */
    public function id(){
        $user = $this->user();
        return $user ? $user->id : null;
    }
    
    /**
     * Validate a user's credentials.
     *
     * @param  array  $credentials
     * @return bool
     */
    public function validate(array $credentials = []){
        return true;
    }
    
    /**
     * Set the current user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    public function setUser(Authenticatable $user){
        $this->user_cached = $user;
    } 
}