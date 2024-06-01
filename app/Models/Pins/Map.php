<?php

namespace App\Models\Pins;

use Illuminate\Database\Eloquent\Model;

class Map extends Model
{
    protected $table = 'pin_map';
    protected $fillable = ['url'];
    public $timestamps = false;
    
    public $validations = [
        'content.url'   => 'required'
    ];
}
