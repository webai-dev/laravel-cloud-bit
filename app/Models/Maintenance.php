<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Maintenance extends Model
{
    protected $table = 'maintenances';
    protected $fillable = [
        'type',
        'description',
        'is_active'
    ];

    public function scopeType($query, $type) {
        return $query->where('type', $type);
    }

    public function scopeDescription($query, $description) {
        return $query->where('description', 'LIKE', '%' . $description . '%');
    }

    public function scopeActive($query, $is_active) {
        return $query->where('is_active', $is_active);
    }
}