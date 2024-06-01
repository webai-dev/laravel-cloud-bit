<?php

namespace App\Models\Pins;

use Illuminate\Database\Eloquent\Model;

class Reminder extends Model
{
    protected $table = 'pin_reminder';
    protected $fillable = ['content','title'];
    public $timestamps = false;
    
    public $validations = [];
}
