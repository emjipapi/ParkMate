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

    protected $rules = [
        'username'  => 'required|string|max:50|unique:admins,username,{{adminId}}',
        'firstname' => 'required|string|max:50',
        'middlename'=> 'nullable|string|max:50',
        'lastname'  => 'required|string|max:50',
        'password'  => 'nullable|string|min:6',
    ];

    public function mount($id)
    {
        $admin = Admin::findOrFail($id);

        $this->adminId   = $admin->admin_id; // matches primary key
        $this->username  = $admin->username;
        $this->firstname = $admin->firstname;
        $this->middlename= $admin->middlename;
        $this->lastname  = $admin->lastname;
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

        $admin->update($data);

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
