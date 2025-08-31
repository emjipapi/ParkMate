<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class AdminForm extends Component
{
    public $username;
    public $firstname;
    public $middlename;
    public $lastname;
    public $password;

    protected $rules = [
        'username'   => 'required|string|max:30|unique:admins,username',
        'firstname'  => 'required|string|max:50',
        'middlename' => 'nullable|string|max:50',
        'lastname'   => 'required|string|max:50',
        'password'   => 'required|string|min:6',
    ];

    public function save()
    {
        $data = $this->validate();
        $data['password'] = Hash::make($data['password']);

        $admin = Admin::create($data);

        // Log activity
        $actorId = Auth::guard('admin')->id();
        ActivityLog::create([
            'actor_type' => 'admin',
            'actor_id'   => $actorId,
            'action'     => 'create',
            'details'    => "Admin {$admin->firstname} {$admin->lastname} was created.",
        ]);

        session()->flash('success', 'Admin created successfully!');
        $this->reset(['username','firstname','middlename','lastname','password']);
    }

    public function render()
    {
        return view('livewire.admin.admin-form');
    }
}
