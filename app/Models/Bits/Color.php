<?php

namespace App\Models\Bits;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Color
 * @package App\Models\Bits
 * @property string color
 * @property int bit_id
 * @property int user_id
 */
class Color extends Model {
    protected $table = 'bit_colors';
    public $timestamps = false;
    protected $fillable = ['color','bit_id','user_id'];
    protected $primaryKey = null;
    public $incrementing = false;

}
