<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;

use Illuminate\Http\Request;
use App\Exceptions\RecaptchaException;

class RecaptchaService{
    
    public function verify(Request $request){
        $client = new Client();
        $body = null;
        
        try{
            $response = $client->post(config('recaptcha.verification_url'),[
                'form_params' => [
                    'secret' => config('recaptcha.secret'),
                    'response' => $request->input('g-recaptcha-response',''),
                    'remoteip' => $request->ip()
                ]
            ]);
            
            $body = json_decode($response->getBody());
            
            if (!$body) 
                throw new RecaptchaException("reCAPTCHA verification failed");
            
            if(!$body->success)
                throw new RecaptchaException("reCAPTCHA verification failed",$body->{"error-codes"});
            
        }catch(TransferException $e){
            throw new RecaptchaException($e->getMessage());
        }
        
        return $body;
    }
}