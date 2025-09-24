<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParkingArea extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'moto_total',
        'created_at',
        'updated_at',
    ];

    public $timestamps = true;
        protected $table = 'parking_areas';

    // If you use guarded/fillable adjust accordingly
    protected $guarded = [];

    // one area has many car slots
    public function carSlots()
    {
        return $this->hasMany(CarSlot::class, 'area_id', 'id');
    }

    // one area has one motorcycle count row
    public function motorcycleCount()
    {
        return $this->hasOne(MotorcycleCount::class, 'area_id', 'id');
    }
}
