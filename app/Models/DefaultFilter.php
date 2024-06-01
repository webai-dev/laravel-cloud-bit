<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DefaultFilter extends Model
{
    protected $table = 'default_filters';
    protected $fillable = [
        'user_id',
        'team_id',
        'sort_by',
        'bits_order',
        'folders_order',
        'files_order',
        'bits_collapse',
        'bits_collapse',
        'folders_collapse',
        'files_collapse',
        'fill_gaps'
    ];    
}
