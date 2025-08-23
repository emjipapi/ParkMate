<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'student_id',
        'employee_id',
        'email',
        'password',
        'rfid_tag',
        'firstname',
        'middlename',
        'lastname',
        'program',
        'department',
        'license_number',
        'profile_picture',
    ];
public $timestamps = false;

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    // App\Models\User.php

public function latestScan()
{
    return $this->hasOne(\App\Models\ActivityLog::class)
                ->latestOfMany();
}

public function currentStatus()
{
    $lastScan = $this->latestScan;
    return $lastScan ? $lastScan->status : 'OUT'; // default to OUT if no scan
}

}
