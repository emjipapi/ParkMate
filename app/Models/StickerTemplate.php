<?php
// app/Models/StickerTemplate.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StickerTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'file_path',
        'width',
        'height',
        'aspect_ratio',
        'element_config',
        'status'
    ];

    protected $casts = [
        'element_config' => 'array',
        'aspect_ratio' => 'decimal:4'
    ];

    public function getFileUrlAttribute()
    {
        return asset('storage/' . $this->file_path);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }
}