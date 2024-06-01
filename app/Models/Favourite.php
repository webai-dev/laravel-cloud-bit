<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Favourite extends Model
{
    protected $table = "user_favourites";
    public $timestamps = false;
    protected $fillable = ['user_id','favourite_id','favourite_type'];
}
