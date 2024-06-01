<?php

namespace App\Services;

use App\Models\Bits\Bit;
use App\Models\Bits\Type;
use App\Models\Share;

use App\Models\User;
use App\Sharing\Permissions;
use App\Exceptions\BitServiceException;
use App\Exceptions\BitValidationException;

use JWTFactory;
use JWTAuth;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use Tymon\JWTAuth\Token;

class BitService{
    
    protected $key;
    protected $url;
    protected $client;
    protected $type;
    
    /**
     * Response timeout in seconds
     * 
     * Is set to be the same as the token expiry time, 
     * so if the token expires, the request times out
     */
    const TIMEOUT = 2 * 60;

    /**
     * BitService constructor.
     * @param Type $type The type of bit to construct the service around
     */
    public function __construct(Type $type){
        $this->key = $type->jwt_key;
        $this->url = $type->base_url;

        $this->type = $type;

        $this->client = new Client([
            'base_uri'    => $this->url,
            'timeout'     => self::TIMEOUT,
            'http_errors' => true
        ]);
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
     * Returns The details of the specified bit from its service
     * @param Bit $bit
     * @return mixed
     * @throws BitServiceException
     * @throws BitValidationException
     */
    public function fetch(Bit $bit){
        $jwt = $this->createJWT([
            'jti' => $bit->id,
            'exp' => time() + self::TIMEOUT,
        ]);
        
        try {
            $response = $this->client->get('',[
                'headers'=> ['Authorization' => "Bearer $jwt"],
            ]);
            return json_decode((string)$response->getBody());
        } catch (TransferException $e) {
            $this->handleException($e);
        }        
    }

    /**
     * Creates a bit in the bit service
     * @param Bit $bit
     * @throws BitServiceException
     * @throws BitValidationException
     */
    public function create(Bit $bit){
        $jwt = $this->createJWT([
            'jti' => $bit->id,
            'exp' => time() + self::TIMEOUT,
        ]);
        
        try {
            $this->client->post('',[
                'headers'=> ['Authorization' => "Bearer $jwt"],
            ]);
        } catch (TransferException $e) {
            $this->handleException($e);
        }
    }

    /**
     * Updates the details of a bit in the bit service
     * @param Bit $bit
     * @param $content
     * @throws BitServiceException
     * @throws BitValidationException
     */
    public function update(Bit $bit,$content){
        $jwt = $this->createJWT([
            'jti' => $bit->id,
            'exp' => time() + self::TIMEOUT,
        ]);
        
        try {
            $this->client->put('',[
                'headers'=> ['Authorization' => "Bearer $jwt"],
                'json'   => $content
            ]);
        } catch (TransferException $e) {
            $this->handleException($e);
        }
    }

    /**
     * Deletes a bit from the bit service
     * @param Bit $bit
     * @throws BitServiceException
     * @throws BitValidationException
     */
    public function remove(Bit $bit){
        $jwt = $this->createJWT([
            'jti' => $bit->id,
            'exp' => time() + self::TIMEOUT
        ]);
        
        try {
            $this->client->delete('',[
                'headers'=> ['Authorization' => "Bearer $jwt"]
            ]);
        } catch (TransferException $e) {
            $this->handleException($e);
        }       
    }

    /**
     * Handles a transfer exception according to its content and status code
     *
     * @param TransferException $e The exception to handle
     * @throws BitServiceException
     * @throws BitValidationException
     */
    public function handleException(TransferException $e){
        if(!$e->hasResponse() || $e->getResponse()->getStatusCode() != 422){
            throw new BitServiceException($e->getMessage(),$this->type->name);
        }
        
        throw new BitValidationException($this->type->name,$e->getResponse()->getBody());
    }
}