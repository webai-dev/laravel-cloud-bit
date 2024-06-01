<?php

namespace App\Models\Bits;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Type
 *
 * @package App\Models\Bits
 * @property int id
 * @property int user_id
 * @property string name
 * @property int width
 * @property int height
 * @property string jwt_key
 * @property string base_url
 * @property string display_url
 * @property boolean public
 * @property boolean draft
 * @property boolean reviewed
 * @property \Illuminate\Support\Collection teams
 */
class Type extends Model {

    protected $table    = 'bit_types';
    protected $visible  = ['id', 'name', 'width', 'height', 'icon', 'pivot', 'color', 'fullscreen', 'background',
        'tagline', 'bits',
    ];
    protected $fillable = ['name', 'base_url', 'display_url', 'jwt_key', 'width', 'height', 'schema'];

    public function bits() {
        return $this->hasMany(Bit::class, 'type_id');
    }

    public function teams() {
        return $this->belongsToMany('App\Models\Teams\Team', 'bit_type_teams', 'type_id', 'team_id');
    }
}
