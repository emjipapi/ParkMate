<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GuestRegistration extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'guest_pass_id',
        'reason',
        'vehicle_type',
        'license_plate',
        'registered_by',
        'office',
    ];

    /**
     * Get the user associated with this registration
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the guest pass associated with this registration
     */
    public function guestPass()
    {
        return $this->belongsTo(GuestPass::class);
    }

    /**
     * Get the admin who registered this guest
     */
    public function registeredBy()
    {
        return $this->belongsTo(Admin::class, 'registered_by');
    }
}
