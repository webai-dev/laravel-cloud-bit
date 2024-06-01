<?php

namespace App\Exceptions;

class BitValidationException extends \Exception{
    
    protected $errors = [];
    
    public function __construct($bit_type,$errors){
        $message = "Validation Failed when creating $bit_type bit";
        parent::__construct($message);
        $this->errors = $errors;
    }
    
    public function getErrors(){
        return json_decode($this->errors);
    }
}