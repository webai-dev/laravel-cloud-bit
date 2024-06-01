<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;

class ValidationServiceProvider extends ServiceProvider{

    public function boot(){
        Validator::extend("bit_schema"
        ,"App\Validation\BitSchemaValidator@validate"
        ,"The provided schema is invalid");
    }
}
