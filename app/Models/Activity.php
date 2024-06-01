<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
  public $timestamps = false;
  public $fillable = ['user_id','action','major','changes','metadata','created_at'];
  
  public function target() {
    return $this->morphTo();
  }
  
  public function user(){
    return $this->belongsTo('App\Models\User');
  }
}