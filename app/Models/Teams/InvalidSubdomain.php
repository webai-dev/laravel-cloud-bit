<?php

namespace App\Models\Teams;

use Illuminate\Database\Eloquent\Model;

class InvalidSubdomain extends Model
{
    protected $table = 'team_invalid_subdomains';
    protected $primaryKey = 'subdomain';
    public $timestamps = false;
    public $fillable = ['subdomain'];
}
