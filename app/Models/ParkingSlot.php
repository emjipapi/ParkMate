<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParkingSlot extends Model
{
    protected $table = 'parking_slots';
    protected $primaryKey = 'slot_id';
    public $timestamps = false;
}
