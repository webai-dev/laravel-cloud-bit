<?php

namespace App\Models\Pins;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    protected $table = 'pin_video';
    protected $fillable = ['url','title'];
    public $timestamps = false;
    
    public $validations = [
        'content.title' => 'required',
        'content.url'   => 'required'
    ];
}
