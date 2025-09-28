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
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;


class UserFormEdit extends Component
{
    use WithFileUploads;

    public $userId;

    // User fields
    public $student_id;
    public $employee_id;
    public $email;
    public $password;
    public $firstname;
    public $middlename;
    public $lastname;
    public $year_section;
    public $address;
    public $contact_number;
    public $license_number;
    public $expiration_date;
    public $profile_picture;
    public $currentProfilePicture;

    protected $middleware = ['auth:admin'];
    public $useStudentId = false;
    public $useEmployeeId = false;
    public $compressedProfilePicture; // holds tmp compressed path (public disk)

    public function updatedProfilePicture()
{
    if ($this->profile_picture instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
        try {
            \Log::info('📥 Profile picture upload detected — starting compression process...');

            $hash = substr(md5(uniqid((string) rand(), true)), 0, 8);
            $prefix = $this->student_id ?: $this->employee_id ?: 'u';
            $filename = 'pf_' . $prefix . '_' . $hash . '.jpg';

            \Log::info('⚙️ Compressing profile picture: ' . ($this->profile_picture->getClientOriginalName() ?? 'unknown'));

            $image = Image::read($this->profile_picture->getPathname())
                ->cover(800, 800, 'center')   // ensures 1:1 aspect
                ->toJpeg(85);

            $tmpPath = 'profile_pics/tmp/' . $filename;
            Storage::disk('public')->put($tmpPath, $image);

            $this->compressedProfilePicture = $tmpPath;

            \Log::info('✅ Profile picture compressed and saved to tmp: ' . $tmpPath);
        } catch (\Exception $e) {
            \Log::error('❌ Failed to compress profile picture on upload: ' . $e->getMessage());
            session()->flash('error', 'Failed to process the profile picture. Please try again.');
            $this->compressedProfilePicture = null;
        }
    } else {
        \Log::warning('⚠️ updatedProfilePicture() called but profile_picture is not a TemporaryUploadedFile.');
    }
}


public function updatedUseEmployeeId($value)
{
    if ($value) {
        // Becoming an employee: clear student-related UI + values
        $this->useStudentId = false;
        $this->student_id = null;

        // employee-specific defaults (clear student-only fields)
        $this->department = null;
        $this->program = null;
        $this->year_section = null;

        // also clear any validation errors for those fields if present
        $this->resetValidation('student_id');
    } else {
        // Becoming NOT an employee (unchecked): clear employee id input
        $this->employee_id = null;
        $this->resetValidation('employee_id');
    }
}
public function updatedUseStudentId($value)
{
    if ($value) {
        // Becoming a student: disable employee mode and clear employee id
        $this->useEmployeeId = false;
        $this->employee_id = null;
        $this->resetValidation('employee_id');
    } else {
        // Becoming NOT a student (unchecked): clear student id input
        $this->student_id = null;
        $this->resetValidation('student_id');
    }
}

    // Vehicles - start with one empty vehicle row
    private function defaultVehicle()
    {
        return [
            'uid' => (string) Str::uuid(),
            'serial_number' => '',
            'type' => 'motorcycle',
            'rfid_tag' => '',
            'license_plate' => '',
            'body_type_model' => '',
            'or_number' => '',
            'cr_number' => ''
        ];
    }

    public $programToDept = [];
    public $departments = [];
    public $department = '';
    public $program = '';
    public $allPrograms = [];
    public $vehicles = [];
    public $programs = [];

    public function mount($id)
    {
        if (!Auth::guard('admin')->check()) {
            abort(403);
        }

        $this->allPrograms = config('programs', []);

        // build reverse lookup (program => dept) and sort each department programs
        foreach ($this->allPrograms as $dept => $programs) {
            sort($programs);
            $this->allPrograms[$dept] = $programs;

            foreach ($programs as $p) {
                $this->programToDept[$p] = $dept;
            }
        }

        $this->departments = array_keys($this->allPrograms);
        sort($this->departments);

        // fetch user and populate fields
        $user = User::with('vehicles')->findOrFail($id);
        $this->userId = $user->id;

        $this->student_id = $user->student_id;
        $this->employee_id = $user->employee_id;
        $this->email = $user->email;
        $this->firstname = $user->firstname;
        $this->middlename = $user->middlename;
        $this->lastname = $user->lastname;
        $this->program = $user->program ? trim($user->program) : '';
        $this->department = $user->department ? trim($user->department) : '';
        $this->year_section = $user->year_section;
        $this->address = $user->address;
        $this->contact_number = $user->contact_number;
        $this->license_number = $user->license_number;
        $this->expiration_date = $user->expiration_date;
        $this->currentProfilePicture = $user->profile_picture;

        // Set checkboxes based on which ID is present
        $this->useStudentId = !empty($this->student_id);
        $this->useEmployeeId = !empty($this->employee_id);

        // populate programs dropdown depending on user's department
        if (!empty($this->department) && isset($this->allPrograms[$this->department])) {
            $this->programs = $this->allPrograms[$this->department];
        } elseif (!empty($this->program) && isset($this->programToDept[$this->program])) {
            // fallback: if department missing but program exists, set department and department programs
            $dept = $this->programToDept[$this->program];
            $this->department = $dept;
            $this->programs = $this->allPrograms[$dept];
        } else {
            // show all programs if no department
            $this->programs = collect($this->allPrograms)->flatten()->sort()->values()->toArray();
        }

        // load vehicles with proper structure
        $this->vehicles = $user->vehicles->map(function ($vehicle) {
            // Extract just the number from S0123 format for editing
            $serialNumber = '';
            if ($vehicle->serial_number && str_starts_with($vehicle->serial_number, 'S')) {
                $serialNumber = ltrim(substr($vehicle->serial_number, 1), '0');
            }

            return [
                'id' => $vehicle->id,
                'uid' => (string) Str::uuid(),
                'serial_number' => $serialNumber,
                'type' => $vehicle->type,
                'rfid_tag' => $vehicle->rfid_tag,
                'license_plate' => $vehicle->license_plate,
                'body_type_model' => $vehicle->body_type_model,
                'or_number' => $vehicle->or_number,
                'cr_number' => $vehicle->cr_number,
            ];
        })->toArray();

        // ensure at least one vehicle row exists
        if (empty($this->vehicles)) {
            $this->vehicles = [$this->defaultVehicle()];
        }
    }

    public function onDepartmentChanged($value)
    {
        $value = trim((string)$value);

        if ($value === '') {
            // reset to show all programs
            $this->programs = collect($this->allPrograms)->flatten()->sort()->values()->toArray();
            $this->program = ''; // clear selection
            $this->department = '';
            return;
        }

        // set program list for dept, keep program only if it belongs here
        $newPrograms = $this->allPrograms[$value] ?? [];
        sort($newPrograms);
        $this->programs = $newPrograms;
        $this->department = $value;

        // if current program not in the department, clear it
        if (!in_array($this->program, $newPrograms, true)) {
            $this->program = '';
        }
    }

    public function onProgramChanged($value)
    {
        $value = trim((string)$value);

        if ($value === '') {
            // user chose "Select Program"
            $this->program = '';
            $this->department = '';
            $this->programs = collect($this->allPrograms)->flatten()->sort()->values()->toArray();
            return;
        }

        // find department quickly via reverse map
        $dept = $this->programToDept[$value] ?? null;

        if ($dept) {
            // IMPORTANT: set programs first so when component re-renders the <option> exists
            $this->programs = $this->allPrograms[$dept];
            $this->department = $dept;
            // set program last so selection persists
            $this->program = $value;
        } else {
            // not found — clear
            $this->program = '';
            $this->department = '';
        }
    }

    public function getFilteredProgramsProperty()
    {
        if (empty($this->department)) {
            return collect($this->allPrograms)->flatten()->sort()->values()->toArray();
        }
        return $this->allPrograms[$this->department] ?? [];
    }

    public function scanRfid($index)
    {
        try {
            $response = Http::timeout(15)->get('http://192.168.1.199:5001/wait-for-scan');
            
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

    public function rules()
    {
        // decide "is employee" from either explicit userType or the checkbox flag
        $isEmployee = ($this->useEmployeeId ?? false) || (($this->userType ?? '') === 'employee');

        // base rules that apply regardless
        $rules = [
            'student_id' => 'nullable|string|max:10',
            'employee_id' => 'nullable|string|max:15',
            'email' => 'required|email|unique:users,email,' . $this->userId,
            'password' => 'nullable|string|min:6', // nullable for edit
            'firstname' => 'required|string|max:50',
            'middlename' => 'nullable|string|max:50',
            'lastname' => 'required|string|max:50',
            'year_section' => 'nullable|string|max:2',
            'address' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:20',
            'license_number' => 'nullable|string|max:13',
            'expiration_date' => 'required|date|after:today',
            'profile_picture' => 'nullable|image|max:5120', // 5 MB
            'vehicles.*.type' => 'required|in:car,motorcycle',
            'vehicles.*.rfid_tag' => [
                'required',
                'string',
                'max:20',
                'distinct',
                Rule::unique('vehicles', 'rfid_tag')->ignore($this->userId, 'user_id'),
            ],
            'vehicles.*.license_plate' => 'nullable|string|max:20',
            'vehicles.*.body_type_model' => 'nullable|string|max:30',
            'vehicles.*.or_number' => 'nullable|string|max:30',
            'vehicles.*.cr_number' => 'nullable|string|max:30',
            'vehicles.*.serial_number' => [
                'required',
                'regex:/^\d{1,6}$/', // only digits, max length 6
                'distinct',
                // Custom validation for uniqueness that processes the serial number
                function ($attribute, $value, $fail) {
                    if (!empty($value)) {
                        $digits = preg_replace('/\D/', '', $value);
                        
                        $processedSerial = null;
                        if (strlen($digits) <= 4) {
                            $processedSerial = 'S' . str_pad($digits, 4, '0', STR_PAD_LEFT);
                        } else {
                            $processedSerial = 'S' . $digits;
                        }
                        
                        // Check if this processed serial number already exists (excluding current user's vehicles)
                        if (Vehicle::where('serial_number', $processedSerial)
                                  ->where('user_id', '!=', $this->userId)
                                  ->exists()) {
                            $fail('The serial number has already been taken.');
                        }
                    }
                },
            ],
        ];

        if ($isEmployee) {
            // employees can't have these — make them nullable/optional
            $rules['program'] = 'nullable|string|max:50';
            $rules['department'] = 'nullable|string|max:50';
            $rules['year_section'] = 'nullable|string|max:2';
        } else {
            // students — make department and program required
            $rules['program'] = 'required|string|max:50';
            $rules['department'] = 'required|string|max:50';
            $rules['year_section'] = 'nullable|string|max:2';
        }

        return $rules;
    }

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

        array_splice($this->vehicles, $index, 1);
    }

    public function update()
    {
 // If we created a compressed tmp image, temporarily clear $this->profile_picture
$originalProfileUpload = null;
if (!empty($this->compressedProfilePicture)) {
    $originalProfileUpload = $this->profile_picture;
    $this->profile_picture = null;
}


        // require either student_id or employee_id
        if (empty($this->student_id) && empty($this->employee_id)) {
            $this->addError('id', 'Please provide either Student ID or Employee ID.');
            return;
        }
        if (!empty($this->student_id) && !empty($this->employee_id)) {
            $this->addError('id', 'Please provide only one: Student ID or Employee ID, not both.');
            return;
        }

        // Validate incoming raw input first
        $data = $this->validate();

        // --- Normalize serials and perform all vehicle-level checks BEFORE updating ---
        $normalizedSerials = [];
        foreach ($this->vehicles as $i => $vehicle) {
            $raw = isset($vehicle['serial_number']) ? (string) $vehicle['serial_number'] : '';
            $digits = preg_replace('/\D/', '', $raw); // keep digits only

            if ($digits === '') {
                $this->addError("vehicles.$i.serial_number", 'Serial number must contain at least one digit.');
                return;
            }

            // Normalization logic: pad up to 4 digits, otherwise keep as-is, always prefix with 'S'
            if (strlen($digits) <= 4) {
                $norm = 'S' . str_pad($digits, 4, '0', STR_PAD_LEFT);
            } else {
                $norm = 'S' . $digits;
            }

            $normalizedSerials[$i] = $norm;
        }

        // check duplicates within submitted normalized set
        if (count(array_unique($normalizedSerials)) !== count($normalizedSerials)) {
            $this->addError('vehicles', 'Two or more vehicles have the same serial number after normalization.');
            return;
        }

        // check DB for collisions against normalized values (excluding current user's vehicles)
        $existing = Vehicle::whereIn('serial_number', array_values($normalizedSerials))
                          ->where('user_id', '!=', $this->userId)
                          ->pluck('serial_number')
                          ->toArray();
        if (!empty($existing)) {
            $this->addError('vehicles', 'One or more vehicle serial numbers already exist: ' . implode(', ', $existing));
            return;
        }

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

        DB::transaction(function () use ($data, $user, $normalizedSerials) {
            // Update user
            $user->update($data);
            if ($this->compressedProfilePicture) {
    try {
        $hash = substr(md5(uniqid((string) rand(), true)), 0, 8);
        $filename = $user->id . '_' . $hash . '.jpg';
        $finalPath = 'profile_pics/' . $filename;

        // read tmp from public disk and put into private disk
        $fileContents = Storage::disk('public')->get($this->compressedProfilePicture);
        Storage::disk('private')->put($finalPath, $fileContents);

        // remove tmp file from public
        Storage::disk('public')->delete($this->compressedProfilePicture);

        // update user record and currentProfilePicture
        $user->update(['profile_picture' => $filename]);
        $this->currentProfilePicture = $filename;

        \Log::info("✅ Profile picture moved from tmp to private: {$finalPath}");
    } catch (\Exception $e) {
        \Log::error("❌ Failed to move compressed profile picture: " . $e->getMessage());
        // swallow or handle — don't abort the entire update if image move fails
    }
}


            // Get incoming vehicle IDs (existing vehicles being kept)
            $incomingIds = collect($this->vehicles)
                ->pluck('id')
                ->filter() // removes null for new vehicles
                ->toArray();

            // Delete vehicles removed from the form
            $user->vehicles()->whereNotIn('id', $incomingIds)->delete();

            // Update or create vehicles
            foreach ($this->vehicles as $idx => $vehicle) {
                $vehicleData = [
                    'type' => $vehicle['type'],
                    'serial_number' => $normalizedSerials[$idx] ?? null,
                    'rfid_tag' => $vehicle['rfid_tag'],
                    'license_plate' => $vehicle['license_plate'] ?? null,
                    'body_type_model' => $vehicle['body_type_model'] ?? null,
                    'or_number' => $vehicle['or_number'] ?? null,
                    'cr_number' => $vehicle['cr_number'] ?? null,
                ];

                if (!empty($vehicle['id'])) {
                    // Update existing vehicle
                    $user->vehicles()->where('id', $vehicle['id'])->update($vehicleData);
                } else {
                    // Create new vehicle
                    $user->vehicles()->create($vehicleData);
                }
            }

            // Log admin action
            $adminId = Auth::guard('admin')->id();
            if (!$adminId) {
                abort(403, 'Admin not authenticated');
            }

            ActivityLog::create([
                'actor_type' => 'admin',
                'actor_id' => $adminId,
                'action' => 'update',
                'details' => "Admin " . Auth::guard('admin')->user()->firstname . " " . Auth::guard('admin')->user()->lastname . " updated user {$user->firstname} {$user->lastname}.",
            ]);
        });

        session()->flash('success', 'User and vehicles updated successfully!');
        $this->profile_picture = null;
$this->compressedProfilePicture = null;
    }

    public function render()
    {
        return view('livewire.admin.user-form-edit');
    }
}