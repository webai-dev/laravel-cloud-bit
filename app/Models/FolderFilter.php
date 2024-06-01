<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FolderFilter extends Model
{
    protected $table = 'folder_filters';
    protected $fillable = [
        'user_id',
        'folder_id',
        'is_shares',
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
