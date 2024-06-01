<?php

namespace App\Models\Pins;

use Illuminate\Database\Eloquent\Model;

class TextNote extends Model
{
    protected $table = 'pin_text';
    protected $fillable = ['content'];
    public $timestamps = false;
    
    public $validations = [
        'content.content' => 'required'
    ];
}
