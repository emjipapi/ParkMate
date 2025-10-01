<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ViolationMessage extends Model
{
    public $timestamps = false; // we only use created_at
    protected $table = 'violation_messages';
    protected $guarded = [];

    // created_at is managed by DB default
    protected $dates = ['created_at'];

    // Relationship to violation
    public function violation()
    {
        return $this->belongsTo(Violation::class, 'violation_id', 'id');
    }

    // Polymorphic sender (Admin or User)
    public function sender()
    {
        return $this->morphTo(null, 'sender_type', 'sender_id');
    }
    
}
