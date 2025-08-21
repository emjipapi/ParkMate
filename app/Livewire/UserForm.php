<?php
namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserForm extends Component
{
    use WithFileUploads;

    public $student_id;
    public $employee_id;
    public $email;
    public $password;
    public $rfid_tag;
    public $firstname;
    public $middlename;
    public $lastname;
    public $program;
    public $department;
    public $license_number;
    public $profile_picture;

    // Hardcoded example departments & programs
    public $departments = ['CCS'];
    public $programs = ['BSCS', 'BSIT', 'BLIS', 'BSIS'];

    protected $rules = [
        'student_id' => 'nullable|string|max:10',
        'employee_id' => 'nullable|string|max:10',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|string|min:6',
        'rfid_tag' => 'required|string|unique:users,rfid_tag|max:10',
        'firstname' => 'required|string|max:50',
        'middlename' => 'nullable|string|max:50',
        'lastname' => 'required|string|max:50',
        'program' => 'required|string|max:50',
        'department' => 'required|string|max:50',
        'license_number' => 'nullable|string|max:11',
        'profile_picture' => 'nullable|image|max:2048',
    ];

public function save()
{
    // Custom validation for student_id / employee_id
    if (empty($this->student_id) && empty($this->employee_id)) {
        $this->addError('id', 'Please provide either Student ID or Employee ID.');
        return;
    }

    if (!empty($this->student_id) && !empty($this->employee_id)) {
        $this->addError('id', 'Please provide only one: Student ID or Employee ID, not both.');
        return;
    }

    $data = $this->validate();

    $data['password'] = Hash::make($data['password']);

if ($this->profile_picture) {
    // Get original extension
    $ext = $this->profile_picture->getClientOriginalExtension();

    // Create random 8-character hash
    $hash = substr(md5(uniqid(rand(), true)), 0, 8);

    // Use student_id or employee_id as prefix
    $prefix = $this->student_id ?: $this->employee_id;

    $filename = $prefix . '.' . $hash . '.' . $ext;

    // Store in storage/app/profile_pics
    $this->profile_picture->storeAs('profile_pics', $filename);

    $data['profile_picture'] = $filename; // save only the filename in DB
}


    User::create($data);

    session()->flash('success', 'User created successfully!');

    $this->reset([
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
        'profile_picture'
    ]);
}

    public function render()
    {
        // Render the Livewire component view
        return view('livewire.user-form');
    }
}
