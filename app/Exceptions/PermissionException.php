<?php

namespace App\Exceptions;

class PermissionException extends \Exception{
    
    public function __construct($shareable_type,$permission){
        parent::__construct(__('permissions.'.$permission,['item'=>$shareable_type]));
    }
}