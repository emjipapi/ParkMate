<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Hash;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class UserFormCreate extends Component
{
    use WithFileUploads;

    // User fields
    public $student_id;
    public $employee_id;
    public $serial_number;   // ✅ add this
    public $email;
    public $password;
    public $firstname;
    public $middlename;
    public $lastname;
    public $program;
    public $department;
    public $year_section;    // ✅ add this
    public $address;         // ✅ add this
    public $contact_number;  // ✅ add this
    public $license_number;
    public $expiration_date; // ✅ add this
    public $profile_picture;

    // Vehicles - start with one empty vehicle row
public $vehicles = [];
private function defaultVehicle()
{
    return [
        'type' => 'motorcycle',
        'rfid_tag' => '',
        'license_plate' => '',
        'body_type_model' => '',
        'or_number' => '',
        'cr_number' => ''
    ];
}
public function mount()
{
    $this->vehicles[] = $this->defaultVehicle();
}

    protected $middleware = ['auth:admin'];

    // Hardcoded example departments & programs
    public $departments = ['CCS'];
    public $programs = ['BSCS', 'BSIT', 'BLIS', 'BSIS'];

    protected $rules = [
        'student_id' => 'nullable|string|max:10',
        'employee_id' => 'nullable|string|max:10',
        'serial_number' => 'required|string|min:5|max:6|unique:users,serial_number',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|string|min:6',
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
        'profile_picture' => 'nullable|image|max:5120', // 5 MB
        'vehicles.*.type' => 'required|in:car,motorcycle',
        'vehicles.*.rfid_tag' => 'required|string|distinct|unique:vehicles,rfid_tag|max:20',
        'vehicles.*.license_plate' => 'nullable|string|max:20',
            'vehicles.*.body_type_model' => 'nullable|string|max:30',
    'vehicles.*.or_number' => 'nullable|string|max:30',
    'vehicles.*.cr_number' => 'nullable|string|max:30',
    ];

    protected $messages = [
        'profile_picture.max' => 'Profile picture must be less than 5 MB.',
    ];

    public function addVehicleRow()
    {
        $this->vehicles[] = $this->defaultVehicle();
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

        // Format serial number
        if (!empty($this->serial_number)) {
            $num = (int) $this->serial_number;

            if ($num < 10000) {
                // pad to 4 digits if less than 10000
                $serialNumber = 'S' . str_pad($num, 4, '0', STR_PAD_LEFT);
            } else {
                // leave as is if 5 digits or more
                $serialNumber = 'S' . $num;
            }

            // assign back to the property so validate() sees it
            $this->serial_number = $serialNumber;
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
        'body_type_model' => $vehicle['body_type_model'] ?? null,
        'or_number' => $vehicle['or_number'] ?? null,
        'cr_number' => $vehicle['cr_number'] ?? null,
            ]);
        }

        // Log admin action
        $adminId = Auth::guard('admin')->id();
        if (!$adminId)
            abort(403, 'Admin not authenticated');

        ActivityLog::create([
            'actor_type' => 'admin',
            'actor_id' => $adminId,
            'action' => 'create',
            'details' => "Admin " . Auth::guard('admin')->user()->firstname . " " . Auth::guard('admin')->user()->lastname . " created user {$user->firstname} {$user->lastname}.",
        ]);

        session()->flash('success', 'User and vehicles created successfully!');

        $this->resetForm();
    }

    private function resetForm()
    {
        $this->reset([
            'student_id',
            'employee_id',
            'email',
            'password',
            'firstname',
            'middlename',
            'lastname',
            'program',
            'department',
            'license_number',
            'profile_picture',
            'vehicles',
            'serial_number',
            'year_section',
            'address',
            'contact_number',
            'expiration_date',
        ]);

        // Reset to one empty vehicle row
            $this->vehicles[] = $this->defaultVehicle();
    }

    public function render()
    {
        return view('livewire.admin.user-form-create');
    }
}