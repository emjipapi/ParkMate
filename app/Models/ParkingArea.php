<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParkingArea extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'allow_students',
        'allow_employees',
        'allow_guests',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'allow_students' => 'boolean',
        'allow_employees' => 'boolean',
        'allow_guests' => 'boolean',
    ];

    public $timestamps = true;
    protected $table = 'parking_areas';

    protected $guarded = [];

    public function carSlots()
    {
        return $this->hasMany(CarSlot::class, 'area_id', 'id');
    }

    public function motorcycleCount()
    {
        return $this->hasOne(MotorcycleCount::class, 'area_id', 'id');
    }
}