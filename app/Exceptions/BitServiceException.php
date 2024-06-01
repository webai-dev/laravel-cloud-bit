<?php

namespace App\Exceptions;

class BitServiceException extends \Exception{
    
    public function __construct($message,$bit_type){
        $message = "Error contacting service for $bit_type bit: $message";
        parent::__construct($message);
    }
}