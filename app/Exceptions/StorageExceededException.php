<?php

namespace App\Exceptions;

class StorageExceededException extends \Exception{

    public function __construct(){
        parent::__construct();
    }
}