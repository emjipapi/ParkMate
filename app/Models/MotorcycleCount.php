<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MotorcycleCount extends Model
{
    protected $fillable = [
        'area_id',
        'available_count',
        'total_available',
    ];

    public function parkingArea()
    {
        return $this->belongsTo(ParkingArea::class, 'area_id');
    }
}
