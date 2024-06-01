<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Recaptcha secret
    |--------------------------------------------------------------------------
    |
    | This string is the recaptcha secret used for validating user requests
    |
    */
    
    'secret' => env('RECAPTCHA_SECRET','6LfOC0IUAAAAAJF64dVNApR_thBvXHON5SfkMwN0'),
    
    'verification_url' => 'https://www.google.com/recaptcha/api/siteverify'
];