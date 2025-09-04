<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Violation extends Model
{
    use HasFactory;

    protected $table = 'violations';

    protected $fillable = [
        'reporter_id',
        'violator_id',      // new
        'area_id',
        'description',
        'license_plate',    // new
        'evidence',
        'status',
        'action_taken',
    ];

    // Reporter relationship
    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    // Violator relationship (optional)
    public function violator()
    {
        return $this->belongsTo(User::class, 'violator_id');
    }

    // Area relationship
    public function area()
    {
        return $this->belongsTo(ParkingArea::class, 'area_id');
    }
    

    
}
