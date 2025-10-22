<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'type',
        'rfid_tag',
        'license_plate',
        'body_type_model',
        'serial_number',
        'or_number',
        'cr_number',
        'sticker_template_id',
    ];

    protected $casts = [
        'rfid_tag' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'sticker_template_id' => 'integer',
    ];

    /**
     * Get the user that owns the vehicle.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter by vehicle type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter by RFID tag
     */
    public function scopeByRfidTag($query, $rfidTag)
    {
        return $query->where('rfid_tag', 'like', '%"'.$rfidTag.'"%');
    }

    public function violations()
    {
        return $this->hasMany(Violation::class, 'license_plate', 'license_plate');
    }
        public function stickerTemplate()
    {
        return $this->belongsTo(StickerTemplate::class, 'sticker_template_id');
    }
}
