<?php

namespace App\Validation;

use Log;

class BitSchemaValidator{
    
    const ALLOWED_ATTRIBUTES = ['type','index','validations','display'];
    const ALLOWED_VALIDATIONS = ['required','url'];
    const ALLOWED_TYPES = ['text','textarea','number','date','password'];
    
    const REQUIRED_ATTRIBUTES = ['type','index'];
    
    protected $error = "The provided schema is invalid";
    
    public function validate($attribute, $value, $parameters, $validator){
        
        $schema = $value;
        if($schema == null || !is_array($schema)) return false;
        
        foreach ($schema as $field => $attributes) {
            
            $invalid_attributes = array_diff(array_keys($attributes),self::ALLOWED_ATTRIBUTES);
            if(count($invalid_attributes) > 0){
                $this->formatError("Invalid attributes:",$invalid_attributes,$field);
                $validator->errors()->add($attribute,$this->error);
                return false;
            }
            
            $missing_attributes = array_diff(self::REQUIRED_ATTRIBUTES,array_keys($attributes));
            if (count($missing_attributes) > 0) {
                $this->formatError("Missing attributes:",$missing_attributes,$field);
                $validator->errors()->add($attribute,$this->error);
                return false;
            }
            
            if (array_key_exists("validations",$attributes)) {
                $invalid_validations = array_diff(array_keys($attributes['validations']),self::ALLOWED_VALIDATIONS);
                if (count($invalid_validations) > 0) {
                    $this->formatError("Invalid validations:",$invalid_validations,$field);
                    $validator->errors()->add($attribute,$this->error);
                    return false;
                }
            }
            
        }
        
        return true;
    }
    
    protected function formatError($message,$data,$field){
        $this->error = $message." '".implode("', '",$data)."' in field '$field'";
    }
    
    public function getError(){
        return $this->error;
    }
}