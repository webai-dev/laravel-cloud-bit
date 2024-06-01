<?php

namespace App\Models\Pins;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $table = 'pin_announcement';
    protected $fillable = ['title','content','date_from','date_to'];
    public $timestamps = false;
    
    public $validations = [
        'content.title'     => 'required',
        'content.date_from' => 'required|date',
        'content.date_to'   => 'required|date'
    ];
}
