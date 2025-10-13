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

    // for checkboxes
    public $permissions = [];
    public $allPermissions = [];

    protected $rules = [
        'username'   => 'required|string|max:30|unique:admins,username',
        'firstname'  => 'required|string|max:50',
        'middlename' => 'nullable|string|max:50',
        'lastname'   => 'required|string|max:50',
        'password'   => 'required|string|min:6',
    ];

    public function mount()
    {
        // load descriptions for tooltips (name => description)
        $this->allPermissions = \DB::table('permissions')->pluck('description', 'name')->toArray();

        // default permissions empty; you can set defaults here if desired
        $this->permissions = [];
    }

    public function toggleGroup($group)
    {
        $groups = [
            'dashboard' => ['analytics_dashboard', 'live_attendance', 'view_map'],
            'parking_slots' => ['manage_map', 'add_parking_area', 'edit_parking_area'],
            'violation_tracking' => ['create_report', 'pending_reports', 'approved_reports', 'for_endorsement', 'submit_approved_report'],
            'users' => ['users_table', 'vehicles_table', 'admins_table', 'create_user', 'edit_user', 'create_admin', 'edit_admin'],
            'sticker_generator' => ['generate_sticker', 'manage_sticker'],
            'activity_log' => ['system_logs', 'entry_exit_logs', 'unknown_tags'],
        ];

        if (! array_key_exists($group, $groups)) return;

        if (in_array($group, $this->permissions)) {
            // If main checked → select all children
            $this->permissions = array_unique(array_merge($this->permissions, $groups[$group]));
        } else {
            // If main unchecked → remove all children
            $this->permissions = array_values(array_diff($this->permissions, $groups[$group]));
        }
    }

    public function syncParent($group)
    {
        $groups = [
            'dashboard' => ['analytics_dashboard', 'live_attendance', 'view_map'],
            'parking_slots' => ['manage_map', 'add_parking_area', 'edit_parking_area'],
            'violation_tracking' => ['create_report', 'pending_reports', 'approved_reports', 'for_endorsement', 'submit_approved_report'],
            'users' => ['users_table', 'vehicles_table', 'admins_table', 'create_user', 'edit_user', 'create_admin', 'edit_admin'],
            'sticker_generator' => ['generate_sticker', 'manage_sticker'],
            'activity_log' => ['system_logs', 'entry_exit_logs', 'unknown_tags'],
        ];

        if (! array_key_exists($group, $groups)) return;

        $children = $groups[$group];
        $hasCheckedChild = count(array_intersect($children, $this->permissions)) > 0;

        if ($hasCheckedChild && !in_array($group, $this->permissions)) {
            // At least one child selected → check main
            $this->permissions[] = $group;
        } elseif (!$hasCheckedChild) {
            // No child selected → uncheck main
            $this->permissions = array_values(array_diff($this->permissions, [$group]));
        }
    }

    public function save()
    {
        $data = $this->validate();

        $data['password'] = Hash::make($data['password']);

        // create admin
        $admin = Admin::create($data);

        // save the permissions JSON
        $admin->permissions = json_encode($this->permissions);
        $admin->save();

        // Log activity
        $actorId = Auth::guard('admin')->id();
        ActivityLog::create([
            'actor_type' => 'admin',
            'actor_id'   => $actorId,
            'action'     => 'create',
            'details'    => "Admin {$admin->firstname} {$admin->lastname} was created.",
        ]);

        session()->flash('success', 'Admin created successfully!');
        $this->reset(['username','firstname','middlename','lastname','password','permissions']);
    }

    public function render()
    {
        return view('livewire.admin.admin-form');
    }
}
