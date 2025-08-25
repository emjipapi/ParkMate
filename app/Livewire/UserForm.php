<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Hash;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class UserForm extends Component
{
    use WithFileUploads;

    // User fields
    public $student_id;
    public $employee_id;
    public $email;
    public $password;
    public $firstname;
    public $middlename;
    public $lastname;
    public $program;
    public $department;
    public $license_number;
    public $profile_picture;

    // Vehicles - start with one empty vehicle row
    public $vehicles = [
        ['type' => 'car', 'rfid_tag' => '', 'license_plate' => '']
    ];

    protected $middleware = ['auth:admin'];

    // Hardcoded example departments & programs
    public $departments = ['CCS'];
    public $programs = ['BSCS', 'BSIT', 'BLIS', 'BSIS'];

    protected $rules = [
        'student_id' => 'nullable|string|max:10',
        'employee_id' => 'nullable|string|max:10',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|string|min:6',
        'firstname' => 'required|string|max:50',
        'middlename' => 'nullable|string|max:50',
        'lastname' => 'required|string|max:50',
        'program' => 'required|string|max:50',
        'department' => 'required|string|max:50',
        'license_number' => 'nullable|string|max:11',
        'profile_picture' => 'nullable|image|max:5120', // 5 MB
        'vehicles.*.type' => 'required|in:car,motorcycle',
        'vehicles.*.rfid_tag' => 'required|string|distinct|unique:vehicles,rfid_tag|max:20',
        'vehicles.*.license_plate' => 'nullable|string|max:20',
    ];

    protected $messages = [
        'profile_picture.max' => 'Profile picture must be less than 5 MB.',
    ];

    public function addVehicleRow()
    {
        $this->vehicles[] = ['type' => 'car', 'rfid_tag' => '', 'license_plate' => ''];
    }

    public function removeVehicleRow($index)
    {
        // Don't allow removing if it's the only vehicle
        if (count($this->vehicles) <= 1) {
            $this->addError('vehicles', 'At least one vehicle is required.');
            return;
        }

        unset($this->vehicles[$index]);
        $this->vehicles = array_values($this->vehicles);
    }

    public function save()
    {
        // Profile picture size check
        if ($this->profile_picture && $this->profile_picture->getSize() > 5 * 1024 * 1024) {
            $this->addError('profile_picture', 'Profile picture must be less than 5 MB.');
            return;
        }

        // Custom ID validation
        if (empty($this->student_id) && empty($this->employee_id)) {
            $this->addError('id', 'Please provide either Student ID or Employee ID.');
            return;
        }

        if (!empty($this->student_id) && !empty($this->employee_id)) {
            $this->addError('id', 'Please provide only one: Student ID or Employee ID, not both.');
            return;
        }

        // Validate the data
        $data = $this->validate();

        // Hash password
        $data['password'] = Hash::make($data['password']);

        // Store profile picture
        if ($this->profile_picture) {
            $ext = $this->profile_picture->getClientOriginalExtension();
            $hash = substr(md5(uniqid(rand(), true)), 0, 8);
            $prefix = $this->student_id ?: $this->employee_id;
            $filename = $prefix . '.' . $hash . '.' . $ext;
            $this->profile_picture->storeAs('profile_pics', $filename);
            $data['profile_picture'] = $filename;
        }

        // Create user
        $user = User::create($data);

        // Create vehicles
        foreach ($this->vehicles as $vehicle) {
            Vehicle::create([
                'user_id' => $user->id,
                'type' => $vehicle['type'],
                'rfid_tag' => $vehicle['rfid_tag'],
                'license_plate' => $vehicle['license_plate'] ?? null,
            ]);
        }

        // Log admin action
        $adminId = Auth::guard('admin')->id();
        if (!$adminId) abort(403, 'Admin not authenticated');

        ActivityLog::create([
            'actor_type' => 'admin',
            'actor_id'   => $adminId,
            'action'     => 'create',
            'details'    => "Admin " . Auth::guard('admin')->user()->firstname . " " . Auth::guard('admin')->user()->lastname . " created user {$user->firstname} {$user->lastname}.",
        ]);

        session()->flash('success', 'User and vehicles created successfully!');

        $this->resetForm();
    }

    private function resetForm()
    {
        $this->reset([
            'student_id', 'employee_id', 'email', 'password',
            'firstname', 'middlename', 'lastname', 'program', 'department',
            'license_number', 'profile_picture', 'vehicles'
        ]);
        
        // Reset to one empty vehicle row
        $this->vehicles = [['type' => 'car', 'rfid_tag' => '', 'license_plate' => '']];
    }

    public function render()
    {
        return view('livewire.user-form');
    }
}