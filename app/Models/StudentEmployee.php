<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class StudentEmployee extends Authenticatable
{
    use Notifiable;

    protected $table = 'users'; // your table for students/employees
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'student_id',
        'employee_id',
        'email',
        'password',
        'firstname',
        'lastname',
        'department',
    ];

    protected $hidden = ['password'];

    public function getAuthPassword()
    {
        return $this->password;
    }
}
