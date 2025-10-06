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
        'area_config'
    ];

    protected $casts = [
        'area_config' => 'array',
    ];

    public function getFileUrlAttribute()
    {
        return asset('storage/' . $this->file_path);
    }
}