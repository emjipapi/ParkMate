<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;
  public $timestamps = false; // Add this line
 protected $fillable = [
        'actor_type', // 'admin' or 'user'
        'actor_id',
        'action',
        'details',
        'created_at',
    ];
    protected $casts = [
        'created_at' => 'datetime',
    ];
    /**
     * Admin actor relationship
     */
    public function admin()
    {
        // Only resolve this if actor_type is 'admin'
        return $this->belongsTo(Admin::class, 'actor_id');
    }

    /**
     * User actor relationship
     */
    public function user()
    {
        // Only resolve this if actor_type is 'user'
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Get the actor regardless of type
     */
    public function getActorAttribute()
    {
        if ($this->actor_type === 'admin') {
            return $this->admin;
        }
        if ($this->actor_type === 'user') {
            return $this->user;
        }
        return null;
    }
}
