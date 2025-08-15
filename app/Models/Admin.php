<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{
    protected $table = 'admins';
    protected $primaryKey = 'admin_id';
    protected $fillable = ['username', 'firstname', 'middlename', 'lastname', 'password'];
    protected $hidden = ['password'];

    public $timestamps = false; // since your table doesn’t have created_at/updated_at
}
