<?php

use Illuminate\Foundation\Inspiring;

use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;
use App\Models\Bits\Bit;
use App\Sharing\Permissions;
use App\Services\Bits\BitServiceImpl;
use App\Services\Bits\VirtualBitService;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->describe('Display an inspiring quote');

Artisan::command('jwt:token {user_id}',function(){
     $user = User::find($this->argument('user_id'));
     if($user == null) {
         $this->error("User not found");
         return;
     }
     
     $token = JWTAuth::fromUser($user); 
     $this->info("Token for user ".$user->name.":");
     $this->line($token);
     
})->describe('Generate a jwt authentication token (for testing purposes)');

Artisan::command('jwt:integration {integration_id} {user_id}',function(){
    /** @var \App\Models\Teams\Integration $integration */
    $integration = \App\Models\Teams\Integration::query()->findOrFail($this->argument('integration_id'));
    /** @var User $user */
    $user = User::query()->findOrFail($this->argument('user_id'));

    $token = \Firebase\JWT\JWT::encode([
        'iss' => $integration->key,
        'sub' => $user->apparatus_id,
        'iat' => time(),
        'exp' => strtotime("+50 years")
    ],$integration->secret);

    $this->info("Token for integration $integration->name, for user $user->name:");
    $this->line($token);

})->describe('Generate a JWT for a given integration');

Artisan::command('jwt:bit {bit_id}',function(){
     $bit = Bit::find($this->argument('bit_id'));
     if($bit == null) {
         $this->error("Bit not found");
         return;
     }

     if (env('app.bits_enabled')) {
         $service = new BitServiceImpl($bit->type);
     } else {
         $service = new VirtualBitService($bit->type);
     }
     
     $user = User::first();
     
     $token = $service->getToken($bit,$user);
     
     $this->info("Token for bit:");
     $this->line($token);
     
})->describe('Generate a bit jwt authentication token (for testing purposes)');