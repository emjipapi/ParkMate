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
        'reporter_type',
        'area_id',
        'description',
        'evidence',
        'violator_id',
        'license_plate',
        'status',
        'action_taken',
        'submitted_at',
        'approved_at',
        'endorsed_at',
        'resolved_at',
    ];

    protected $casts = [
        'evidence' => 'array',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'endorsed_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    // Reporter relationship
public function reporter()
{
    return $this->morphTo();
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

    // Helper methods for status transitions
    public function markAsSubmitted()
    {
        $this->status = 'pending';
        $this->submitted_at = now();
        $this->save();
    }

    public function markAsApproved()
    {
        $this->status = 'approved';
        $this->approved_at = now();
        $this->save();
    }

    public function markForEndorsement()
    {
        $this->status = 'for_endorsement';
        $this->endorsed_at = now();
        $this->save();
    }

    public function markAsResolved()
    {
        $this->status = 'resolved';
        $this->resolved_at = now();
        $this->save();
    }

    public function markAsRejected()
    {
        $this->status = 'rejected';
        $this->save();
    }

    // Scope for filtering by status
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeForEndorsement($query)
    {
        return $query->where('status', 'for_endorsement');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
    public function vehicle()
{
    return $this->belongsTo(Vehicle::class, 'license_plate', 'license_plate');
}
    public function latestMessage()
    {
        return $this->hasOne(ViolationMessage::class, 'violation_id', 'id')->latestOfMany();
    }
        public function messages()
    {
        return $this->hasMany(ViolationMessage::class, 'violation_id', 'id');
    }

}