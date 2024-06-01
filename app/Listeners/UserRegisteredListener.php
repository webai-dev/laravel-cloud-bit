<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;
use App\Models\Teams\Team;
use App\Models\Teams\Invitation;
use Mail;
use App\Mail\Onboarding;

class UserRegisteredListener{
    
    public function handle(Registered $event){
        Mail::to($event->user->email)->send(new Onboarding($event->user));
    }
}