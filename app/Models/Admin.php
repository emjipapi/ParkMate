<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
class Admin extends Authenticatable
{
    use Notifiable, SoftDeletes;

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
    public function reportedViolations()
{
    return $this->morphMany(\App\Models\Violation::class, 'reporter');
}
public function hasPermission($permission)
{
    $permissions = json_decode($this->permissions ?? '[]', true);
    return in_array($permission, $permissions);
}

}
