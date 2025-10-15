<?php

namespace App\Models;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

class GuestPass extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'guest_passes';

    protected $fillable = [
        'name',
        'rfid_tag',
        'status',
        'reason',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected function currentLocation(): Attribute
    {
        return Attribute::make(
            get: function () {
                // First, ensure a user is actually associated with this pass.
                if (!$this->user) {
                    return 'N/A';
                }

                // Directly query the ActivityLog for the latest entry for this user.
                $latestLog = ActivityLog::where('actor_id', $this->user->id)
                                  ->where('actor_type', 'user') // Ensure we only get user logs
                                  ->latest('created_at')
                                  ->first();
                
                // Return the 'details' column, which contains the location info.
                return $latestLog?->details ?? 'Not yet scanned';
            }
        );
    }
}