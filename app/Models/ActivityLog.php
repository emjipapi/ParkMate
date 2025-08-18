<?php

// app/Models/ActivityLog.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'rfid_tag',
        'status',
    ];

    // Optional: define relation to user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
