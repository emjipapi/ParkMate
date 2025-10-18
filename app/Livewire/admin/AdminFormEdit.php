<?php
namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class AdminFormEdit extends Component
{
    public $adminId;
    public $username;
    public $firstname;
    public $middlename;
    public $lastname;
    public $password;
public $permissions = [];
public $allPermissions = [];
public $originalPermissions = [];


public $isSuperAdmin = false;

public function toggleGroup($group)
{
    $groups = [
        'dashboard' => ['analytics_dashboard', 'live_attendance', 'manage_guest', 'manage_guest_tag'],
        'parking_slots' => ['manage_map', 'add_parking_area', 'edit_parking_area'],
        'violation_tracking' => ['create_report', 'pending_reports', 'approved_reports', 'for_endorsement', 'submit_approved_report'],
        'users' => ['users_table', 'vehicles_table', 'admins_table', 'create_user', 'edit_user', 'create_admin', 'edit_admin'],
        'sticker_generator' => ['generate_sticker', 'manage_sticker'],
        'activity_log' => ['system_logs', 'entry_exit_logs', 'unknown_tags'],
    ];

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
        'dashboard' => ['analytics_dashboard', 'live_attendance', 'manage_guest', 'manage_guest_tag'],
        'parking_slots' => [ 'manage_map', 'add_parking_area', 'edit_parking_area'],
        'violation_tracking' => ['create_report', 'pending_reports', 'approved_reports', 'for_endorsement', 'submit_approved_report'],
        'users' => ['users_table', 'vehicles_table', 'admins_table', 'create_user', 'edit_user', 'create_admin', 'edit_admin'],
        'sticker_generator' => ['generate_sticker', 'manage_sticker'],
        'activity_log' => ['system_logs', 'entry_exit_logs', 'unknown_tags'],
    ];

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
    protected $rules = [
        'username'  => 'required|string|max:50|unique:admins,username,{{adminId}}',
        'firstname' => 'required|string|max:50',
        'middlename'=> 'nullable|string|max:50',
        'lastname'  => 'required|string|max:50',
        'password'  => 'nullable|string|min:6',
        'permissions' => 'required|array|min:1',
    ];

    public function mount($id)
    {
        $admin = Admin::findOrFail($id);

        $this->adminId   = $admin->admin_id; // matches primary key
        $this->username  = $admin->username;
        $this->firstname = $admin->firstname;
        $this->middlename= $admin->middlename;
        $this->lastname  = $admin->lastname;
        $this->permissions = json_decode($admin->permissions ?? '[]', true);
        $this->originalPermissions = $this->permissions ?? [];
        $this->allPermissions = \DB::table('permissions')->pluck('description', 'name')->toArray();
        $this->isSuperAdmin = $this->adminId == 1;
    }

    public function update()
    {
         $this->rules['username'] = 'required|string|max:50|unique:admins,username,' . $this->adminId . ',admin_id';
        $data = $this->validate();

        $admin = Admin::findOrFail($this->adminId);

        if (!empty($this->password)) {
            $data['password'] = Hash::make($this->password);
        } else {
            unset($data['password']);
        }
            if ($this->isSuperAdmin && $this->permissions !== $this->originalPermissions) {
        abort(403, 'You are not allowed to modify the Super Admin permissions.');
    }

        $admin->update($data);
$admin->permissions = json_encode($this->permissions);
$admin->save();
        // Log the action
        $actor = Auth::guard('admin')->user();
        ActivityLog::create([
            'actor_type' => 'admin',
            'actor_id'   => $actor->admin_id,
            'action'     => 'update',
            'details'    => "Admin {$actor->firstname} {$actor->lastname} updated admin {$admin->firstname} {$admin->lastname}.",
        ]);

        session()->flash('success', 'Admin updated successfully!');
        
    }

    public function render()
    {
        return view('livewire.admin.admin-form-edit');
    }
}
