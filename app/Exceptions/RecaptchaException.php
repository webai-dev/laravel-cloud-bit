<?php

namespace App\Exceptions;

class RecaptchaException extends \Exception{
    
    protected $errors = [];
    
    public function __construct($message,$errors = []){
        $this->errors = [];
        parent::__construct($message);
    }
    
    public function getErrors(){
        return $this->errors;
    }
}