<?php

namespace App\Models\Pins;

use Illuminate\Database\Eloquent\Model;

class Type extends Model
{
    protected $table = 'pin_types';
    protected $fillable = ['name','label'];
    public $timestamps = false;
}
