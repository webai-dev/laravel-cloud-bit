<?php

namespace App\Models\Pins;

use Illuminate\Database\Eloquent\Model;

class Photo extends Model
{
    protected $table = 'pin_photo';
    protected $fillable = ['title','url'];
    public $timestamps = false;
    
    public $validations = [
        'content.title' => 'required',
        'content.url'   => 'required'
    ];
}
