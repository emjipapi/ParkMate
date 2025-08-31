<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use Notifiable;

    protected $table = 'admins';
    protected $primaryKey = 'admin_id'; // your PK
    public $timestamps = false;

    protected $fillable = [
        'username',
        'firstname',
        'middlename',
        'lastname',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    // Tell Laravel the password column
    public function getAuthPassword()
    {
        return $this->password; // must match DB column exactly (lowercase)
    }
}
