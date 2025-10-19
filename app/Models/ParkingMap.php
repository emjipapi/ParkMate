<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParkingMap extends Model
{
    protected $fillable = [
        'name',
        'file_path',
        'width',
        'height',
        'aspect_ratio',
        'status',
        'area_config',
        'is_default'
    ];

    protected $casts = [
        'area_config' => 'array',
        'is_default' => 'boolean',
    ];

    public function getFileUrlAttribute()
    {
        return asset('storage/' . $this->file_path);
    }
}