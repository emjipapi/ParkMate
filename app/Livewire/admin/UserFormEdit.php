<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Hash;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class UserFormEdit extends Component
{
    use WithFileUploads;

    public $userId;

    // User fields
    public $student_id;
    public $employee_id;
    public $serial_number;
    public $email;
    public $password;
    public $firstname;
    public $middlename;
    public $lastname;
    public $program;
    public $department;
    public $year_section;
    public $address;
    public $contact_number;
    public $license_number;
    public $expiration_date;
    public $profile_picture;
    public $currentProfilePicture;

    // Vehicles
    public $vehicles = [];

    protected $middleware = ['auth:admin'];

    // Hardcoded example departments & programs
    public $departments = ['CCS'];
    public $programs = ['BSCS', 'BSIT', 'BLIS', 'BSIS'];

    protected $rules = [
        'student_id' => 'nullable|string|max:10',
        'employee_id' => 'nullable|string|max:10',
'serial_number' => 'required|string|min:5|max:6', // Remove unique from here
    'email' => 'required|email', // Remove unique from here
        'password' => 'nullable|string|min:6',
        'firstname' => 'required|string|max:50',
        'middlename' => 'nullable|string|max:50',
        'lastname' => 'required|string|max:50',
        'program' => 'required|string|max:50',
        'department' => 'required|string|max:50',
        'year_section' => 'nullable|string|max:2',
        'address' => 'nullable|string|max:255',
        'contact_number' => 'nullable|string|max:20',
        'license_number' => 'nullable|string|max:13',
        'expiration_date' => 'required|date|after:today',
        'profile_picture' => 'nullable|image|max:5120',
        'vehicles.*.type' => 'required|in:car,motorcycle',
        'vehicles.*.rfid_tag' => 'required|string|distinct|max:20',
        'vehicles.*.license_plate' => 'nullable|string|max:20',
        'vehicles.*.body_type_model' => 'nullable|string|max:30',
        'vehicles.*.or_number' => 'nullable|string|max:30',
        'vehicles.*.cr_number' => 'nullable|string|max:30',
    ];

    protected $messages = [
        'profile_picture.max' => 'Profile picture must be less than 5 MB.',
    ];

    public function mount($id)
    {
        $user = User::with('vehicles')->findOrFail($id);
        $this->userId = $user->id;

        // Load user fields
        $this->student_id = $user->student_id;
        $this->employee_id = $user->employee_id;
        $this->serial_number = $user->serial_number;
        $this->email = $user->email;
        $this->firstname = $user->firstname;
        $this->middlename = $user->middlename;
        $this->lastname = $user->lastname;
        $this->program = $user->program;
        $this->department = $user->department;
        $this->year_section = $user->year_section;
        $this->address = $user->address;
        $this->contact_number = $user->contact_number;
        $this->license_number = $user->license_number;
        $this->expiration_date = $user->expiration_date;
        $this->currentProfilePicture = $user->profile_picture;

        // Load vehicles
        $this->vehicles = $user->vehicles->map(function ($vehicle) {
            return [
                'type' => $vehicle->type,
                'rfid_tag' => $vehicle->rfid_tag,
                'license_plate' => $vehicle->license_plate,
                'body_type_model' => $vehicle->body_type_model,
                'or_number' => $vehicle->or_number,
                'cr_number' => $vehicle->cr_number,
            ];
        })->toArray();

        // Ensure at least one vehicle row exists
        if (empty($this->vehicles)) {
            $this->vehicles = [[
                'type' => 'car',
                'rfid_tag' => '',
                'license_plate' => '',
                'body_type_model' => '',
                'or_number' => '',
                'cr_number' => ''
            ]];
        }
    }

    public function addVehicleRow()
    {
        $this->vehicles[] = [
            'type' => 'car',
            'rfid_tag' => '',
            'license_plate' => '',
            'body_type_model' => '',
            'or_number' => '',
            'cr_number' => ''
        ];
    }
public function scanRfid($index)
{
    try {
        $response = Http::timeout(10)->get('http://192.168.1.199:5001/wait-for-scan');
        
        if ($response->successful()) {
            $data = $response->json();
            
            if ($data['success'] && isset($data['rfid_tag'])) {
                $this->vehicles[$index]['rfid_tag'] = $data['rfid_tag'];
            } else {
                $this->addError("vehicles.$index.rfid_tag", $data['error'] ?? 'No RFID scan received');
            }
        } else {
            $this->addError("vehicles.$index.rfid_tag", 'Failed to connect to RFID scanner.');
        }
    } catch (\Exception $e) {
        $this->addError("vehicles.$index.rfid_tag", 'RFID scanner not running or timeout.');
    }
}
    public function removeVehicleRow($index)
    {
        if (count($this->vehicles) <= 1) {
            $this->addError('vehicles', 'At least one vehicle is required.');
            return;
        }

        unset($this->vehicles[$index]);
        $this->vehicles = array_values($this->vehicles);
    }

    public function update()
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

    // Adjust rules for unique fields
    $this->rules['email'] = 'required|email|unique:users,email,' . $this->userId;
    $this->rules['serial_number'] = 'required|string|min:5|max:6|unique:users,serial_number,' . $this->userId;

    // Format serial number ONLY if it doesn't already start with 'S'
    if (!empty($this->serial_number) && !str_starts_with($this->serial_number, 'S')) {
        $num = (int) $this->serial_number;

        if ($num < 10000) {
            $serialNumber = 'S' . str_pad($num, 4, '0', STR_PAD_LEFT);
        } else {
            $serialNumber = 'S' . $num;
        }

        $this->serial_number = $serialNumber;
    }
        $data = $this->validate();

        $user = User::findOrFail($this->userId);

        // Only hash password if provided
        if (!empty($this->password)) {
            $data['password'] = Hash::make($this->password);
        } else {
            unset($data['password']);
        }

        // Handle profile picture update
        if ($this->profile_picture) {
            $ext = $this->profile_picture->getClientOriginalExtension();
            $hash = substr(md5(uniqid(rand(), true)), 0, 8);
            $prefix = $this->student_id ?: $this->employee_id;
            $filename = $prefix . '.' . $hash . '.' . $ext;
            $this->profile_picture->storeAs('profile_pics', $filename);
            $data['profile_picture'] = $filename;

            $this->currentProfilePicture = $filename;
        } else {
            $data['profile_picture'] = $this->currentProfilePicture;
        }

        // Update user
        $user->update($data);

        // Replace vehicles
        $user->vehicles()->delete();
        foreach ($this->vehicles as $vehicle) {
            Vehicle::create([
                'user_id' => $user->id,
                'type' => $vehicle['type'],
                'rfid_tag' => $vehicle['rfid_tag'],
                'license_plate' => $vehicle['license_plate'] ?? null,
                'body_type_model' => $vehicle['body_type_model'] ?? null,
                'or_number' => $vehicle['or_number'] ?? null,
                'cr_number' => $vehicle['cr_number'] ?? null,
            ]);
        }

        // Log admin action
        $adminId = Auth::guard('admin')->id();
        if (!$adminId) abort(403, 'Admin not authenticated');

        ActivityLog::create([
            'actor_type' => 'admin',
            'actor_id'   => $adminId,
            'action'     => 'update',
            'details'    => "Admin " . Auth::guard('admin')->user()->firstname . " " . Auth::guard('admin')->user()->lastname . " updated user {$user->firstname} {$user->lastname}.",
        ]);

        session()->flash('success', 'User and vehicles updated successfully!');
        
    }

    public function render()
    {
        return view('livewire.admin.user-form-edit');
    }
}
