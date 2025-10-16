<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarSlot extends Model
{
    protected $table = 'car_slots';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = ['area_id', 'label', 'occupied', 'disabled'];

    protected $casts = [
        'occupied' => 'boolean',
        'disabled' => 'boolean',
    ];
}