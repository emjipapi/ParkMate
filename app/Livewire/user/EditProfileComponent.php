<?php

namespace App\Livewire\User;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Intervention\Image\Laravel\Facades\Image;
use Livewire\Component;
use Livewire\WithFileUploads;

class EditProfileComponent extends Component
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

    public $year_section;    // ✅ add this

    public $address;         // ✅ add this

    public $contact_number;  // ✅ add this

    public $license_number;

    public $expiration_date; // ✅ add this

    public $profile_picture;

    // this is a user-side profile editor — require web auth
    protected $middleware = ['auth'];

    public $useStudentId = false;

    public $useEmployeeId = false;

    public $compressedProfilePicture; // holds the compressed tmp path (e.g. profile_pics/tmp/...)

    public function updatedProfilePicture()
    {
        // Only run when we get the Livewire TemporaryUploadedFile instance
        if ($this->profile_picture instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
            try {
                \Log::info('📥 Profile picture upload detected — starting compression process...');

                $hash = substr(md5(uniqid((string) rand(), true)), 0, 8);
                $prefix = $this->student_id ?: $this->employee_id ?: 'u';
                $filename = 'pf_' . $prefix . '_' . $hash . '.jpg';

                \Log::info('⚙️ Compressing profile picture: ' . ($this->profile_picture->getClientOriginalName() ?? 'unknown'));

                $image = Image::read($this->profile_picture->getPathname())
                    ->cover(800, 800, 'center')   // adjust max dimensions if you prefer
                    ->toJpeg(50);           // quality 0-100

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
            'rfid_tags' => [''],
            'license_plate' => '',
            'body_type_model' => '',
            'or_number' => '',
            'cr_number' => '',
        ];
    }

    public $programToDept = [];

    public $departments = [];

    public $department = '';

    public $program = '';

    public $allPrograms = [];

    public $vehicles = [];

    public $programs = [];

    // debug helpers
    public $rawUser = null;
    public $rawVehicles = [];

    public function mount()
    {
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

        // load current authenticated user (web guard)
        if (!Auth::check()) {
            abort(403);
        }

        // Use a fresh DB model to avoid any guarded/partial attributes coming from the auth user instance
        $auth = Auth::user();
        $user = User::find($auth->id);
        if (!$user) {
            abort(404, 'User not found');
        }

        // populate basic fields from the signed-in user
        $this->student_id = $user->student_id ?? null;
        $this->employee_id = $user->employee_id ?? null;
        $this->email = $user->email ?? null;
        // we don't expose password here
        $this->firstname = $user->firstname ?? null;
        $this->middlename = $user->middlename ?? null;
        $this->lastname = $user->lastname ?? null;
        $this->year_section = $user->year_section ?? null;
        $this->address = $user->address ?? null;
        $this->contact_number = $user->contact_number ?? null;
        $this->license_number = $user->license_number ?? null;
        $this->expiration_date = $user->expiration_date ?? null;

        $this->useStudentId = !empty($this->student_id) && empty($this->employee_id);
        $this->useEmployeeId = !empty($this->employee_id) && empty($this->student_id);

        // load vehicles for this user directly from DB (avoid any relation caching / guard differences)
        $loaded = [];
        $userVehicles = Vehicle::where('user_id', $user->id)->get();
        // If no vehicles found for the exact user_id, try a fallback lookup by employee_id or student_id.
        // This handles cases where vehicles were imported or associated with a different user row but share the same employee/student identifier.
        if ($userVehicles->isEmpty()) {
            if (!empty($user->employee_id)) {
                $altUser = User::where('employee_id', $user->employee_id)
                    ->where('id', '!=', $user->id)
                    ->first();

                if ($altUser) {
                    \Log::info('EditProfileComponent: no vehicles for user_id='.$user->id.'; falling back to user_id='.$altUser->id.' via employee_id='.$user->employee_id);
                    $userVehicles = Vehicle::where('user_id', $altUser->id)->get();
                    // also pull profile fields from alt user if main user appears empty
                    if (empty($user->address) && !empty($altUser->address)) {
                        $user->address = $altUser->address;
                    }
                    if (empty($user->contact_number) && !empty($altUser->contact_number)) {
                        $user->contact_number = $altUser->contact_number;
                    }
                    if (empty($user->license_number) && !empty($altUser->license_number)) {
                        $user->license_number = $altUser->license_number;
                    }
                    if (empty($user->expiration_date) && !empty($altUser->expiration_date)) {
                        $user->expiration_date = $altUser->expiration_date;
                    }
                }
            }

            // student_id fallback
            if ($userVehicles->isEmpty() && !empty($user->student_id)) {
                $altUser = User::where('student_id', $user->student_id)
                    ->where('id', '!=', $user->id)
                    ->first();

                if ($altUser) {
                    \Log::info('EditProfileComponent: no vehicles for user_id='.$user->id.'; falling back to user_id='.$altUser->id.' via student_id='.$user->student_id);
                    $userVehicles = Vehicle::where('user_id', $altUser->id)->get();

                    if (empty($user->address) && !empty($altUser->address)) {
                        $user->address = $altUser->address;
                    }
                    if (empty($user->contact_number) && !empty($altUser->contact_number)) {
                        $user->contact_number = $altUser->contact_number;
                    }
                    if (empty($user->license_number) && !empty($altUser->license_number)) {
                        $user->license_number = $altUser->license_number;
                    }
                    if (empty($user->expiration_date) && !empty($altUser->expiration_date)) {
                        $user->expiration_date = $altUser->expiration_date;
                    }
                }
            }
        }
        foreach ($userVehicles as $v) {
            $tags = [];
            // prefer rfid_tags column if present and JSON
            if (isset($v->rfid_tags) && $v->rfid_tags) {
                $decoded = json_decode($v->rfid_tags, true);
                if (is_array($decoded)) {
                    $tags = $decoded;
                } else {
                    $tags = [$v->rfid_tags];
                }
            } elseif (isset($v->rfid_tag) && $v->rfid_tag) {
                // legacy: could be JSON or plain string
                $decoded = json_decode($v->rfid_tag, true);
                if (is_array($decoded)) {
                    $tags = $decoded;
                } else {
                    $tags = [$v->rfid_tag];
                }
            }

            if (empty($tags)) {
                $tags = [''];
            }

            $loaded[] = [
                'uid' => $v->id,
                'serial_number' => $v->serial_number ?? '',
                'type' => $v->type ?? 'motorcycle',
                'rfid_tags' => $tags,
                'license_plate' => $v->license_plate ?? '',
                'body_type_model' => $v->body_type_model ?? '',
                'or_number' => $v->or_number ?? '',
                'cr_number' => $v->cr_number ?? '',
            ];
        }

        $this->vehicles = count($loaded) ? $loaded : [$this->defaultVehicle()];

    // fill debug raw records
    $this->rawUser = User::find($user->id)?->toArray();
    $this->rawVehicles = Vehicle::where('user_id', $user->id)->get()->toArray();

    }

    public function onDepartmentChanged($value)
    {
        $value = trim((string) $value);

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
        $value = trim((string) $value);

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
                    // ensure rfid_tags array exists
                    if (!isset($this->vehicles[$index]['rfid_tags']) || !is_array($this->vehicles[$index]['rfid_tags'])) {
                        $this->vehicles[$index]['rfid_tags'] = [''];
                    }
                    $this->vehicles[$index]['rfid_tags'][0] = $data['rfid_tag'];
                } else {
                    $this->addError("vehicles.$index.rfid_tags.0", $data['error'] ?? 'No RFID scan received');
                }
            } else {
                $this->addError("vehicles.$index.rfid_tags.0", 'Failed to connect to RFID scanner.');
            }
        } catch (\Exception $e) {
            $this->addError("vehicles.$index.rfid_tags.0", 'RFID scanner not running or timeout.');
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
            // 'serial_number' => 'required|string|min:5|max:6|unique:users,serial_number',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'firstname' => 'required|string|max:50',
            'middlename' => 'nullable|string|max:50',
            'lastname' => 'required|string|max:50',
            // year_section stays nullable by default (adjust below if you want it required for students)
            'year_section' => 'nullable|string|max:2',
            'address' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:20',
            'license_number' => 'nullable|string|max:13',
            'expiration_date' => 'required|date|after:today',
            'profile_picture' => 'nullable|image|max:10240', // 5 MB
            'vehicles.*.type' => 'required|in:car,motorcycle',
            'vehicles.*.rfid_tags' => 'array',
            'vehicles.*.rfid_tags.*' => 'nullable|string|max:20',
            'vehicles.*.license_plate' => 'nullable|string|max:20',
            'vehicles.*.body_type_model' => 'nullable|string|max:30',
            'vehicles.*.or_number' => 'nullable|string|max:30',
            'vehicles.*.cr_number' => 'nullable|string|max:30',
            'vehicles.*.serial_number' => [
                'required',
                'regex:/^\d{1,6}$/', // only digits, max length 6
                'distinct',
                Rule::unique('vehicles', 'serial_number'),
            ],

        ];

        if ($isEmployee) {
            // employees can't have these — make them nullable/optional
            $rules['program'] = 'nullable|string|max:50';
            $rules['department'] = 'nullable|string|max:50';
            // keep year_section nullable for employees
            $rules['year_section'] = 'nullable|string|max:2';
        } else {
            // students — make department and program required
            $rules['program'] = 'required|string|max:50';
            $rules['department'] = 'required|string|max:50';
            // if you want year_section required for students, change to 'required|string|max:2'
            $rules['year_section'] = 'nullable|string|max:2';
        }

        return $rules;
    }

    protected $messages = [
        'profile_picture.max' => 'Profile picture must be less than 10 MB.',
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

    protected function compactRfidTags(?array $rawTags): array
    {
        if (empty($rawTags) || !is_array($rawTags)) {
            return [];
        }

        // Trim each tag and remove empties
        $clean = array_values(array_filter(array_map(function ($t) {
            return is_string($t) ? trim($t) : null;
        }, $rawTags), function ($v) {
            return $v !== null && $v !== '';
        }));

        return $clean;
    }

    public function save()
    {
        // quick size check for picture
        // if ($this->profile_picture && $this->profile_picture->getSize() > 5 * 1024 * 1024) {
        //     $this->addError('profile_picture', 'Profile picture must be less than 5 MB.');
        //     return;
        // }

        // require either student_id or employee_id
        if (empty($this->student_id) && empty($this->employee_id)) {
            $this->addError('id', 'Please provide either Student ID or Employee ID.');

            return;
        }
        if (!empty($this->student_id) && !empty($this->employee_id)) {
            $this->addError('id', 'Please provide only one: Student ID or Employee ID, not both.');

            return;
        }
        $originalProfileUpload = null;
        if (!empty($this->compressedProfilePicture)) {
            $originalProfileUpload = $this->profile_picture;
            $this->profile_picture = null;
        }
        // Validate incoming raw input first (this will check email uniqueness etc.)
        $data = $this->validate();

        // --- Normalize serials and perform all vehicle-level checks BEFORE creating anything ---
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

        // check DB for collisions against normalized values
        $existing = Vehicle::whereIn('serial_number', array_values($normalizedSerials))->pluck('serial_number')->toArray();
        if (!empty($existing)) {
            $this->addError('vehicles', 'One or more vehicle serial numbers already exist: ' . implode(', ', $existing));

            return;
        }

        // Good — all checks passed. Proceed to create everything inside a transaction.
        // Hash password and prepare $data for insert
        $data['password'] = Hash::make($data['password'] ?? '');

        // Log normalized serials for debugging (optional)
        \Log::debug('Normalized serials before insert: ' . json_encode($normalizedSerials));
    $allTags = [];
    foreach ($this->vehicles as $i => $veh) {
        $tags = $this->compactRfidTags($veh['rfid_tags'] ?? []);
        if (count($tags) === 0) {
            $this->addError("vehicles.$i.rfid_tags.0", 'At least one RFID tag is required for each vehicle.');
            return;
        }
        $allTags = array_merge($allTags, $tags);
    }

    // check duplicates within submission
    if (count($allTags) !== count(array_unique($allTags))) {
        $this->addError('vehicles', 'Duplicate RFID tag values found in your submitted vehicles.');
        return;
    }

// check duplicates in DB (handle JSON and plain strings)
$duplicateTags = [];

foreach ($allTags as $tag) {
    $exists = Vehicle::where(function ($query) use ($tag) {
        $query->where('rfid_tag', $tag)
              ->orWhere('rfid_tag', 'like', '%"' . $tag . '"%');
    })->exists();

    if ($exists) {
        $duplicateTags[] = $tag;
    }
}

if (!empty($duplicateTags)) {
    $this->addError(
        'vehicles',
        'The following RFID tag(s) already exist in the database: ' . implode(', ', $duplicateTags)
    );
    return;
}



        DB::transaction(function () use ($data, $normalizedSerials) {
            // create user
            $user = User::create($data);

            // Handle profile picture with user ID using compressed version
            if ($this->compressedProfilePicture) {
                // Generate the final filename
                $hash = substr(md5(uniqid((string) rand(), true)), 0, 8);
                $filename = $user->id . '_' . $hash . '.jpg'; // Always .jpg since compressed to JPEG

                // Define final path using new filename
                $finalPath = 'profile_pics/' . $filename;

                // Read the image from the public tmp folder
                $fileContents = Storage::disk('public')->get($this->compressedProfilePicture);

                // Save it to the private disk
                Storage::disk('private')->put($finalPath, $fileContents);

                // Delete the original tmp file
                Storage::disk('public')->delete($this->compressedProfilePicture);

                // Update the user with the new profile picture filename
                $user->update(['profile_picture' => $filename]);

                \Log::info('✅ Profile picture moved from tmp to final: ' . $finalPath);
            }
            // foreach ($this->vehicles as $i => $veh) {
            //     $tags = $this->compactRfidTags($veh['rfid_tags'] ?? []);
            //     if (count($tags) === 0) {
            //         $this->addError("vehicles.$i.rfid_tags.0", 'At least one RFID tag is required for each vehicle.');
            //         return;
            //     }
            // }


            // create vehicles
    foreach ($this->vehicles as $idx => $vehicle) {
        $rawTags = $vehicle['rfid_tags'] ?? [];
        $tags = $this->compactRfidTags($rawTags);

        // firstTag for legacy column
        $tags = array_values(array_filter($vehicle['rfid_tags'] ?? [], fn($t) => !empty($t)));

        $vehicleData = [
            'user_id' => $user->id,
            'type' => $vehicle['type'],
            'serial_number' => $normalizedSerials[$idx] ?? null,
            'rfid_tag' => json_encode($tags),
            'license_plate' => $vehicle['license_plate'] ?? null,
            'body_type_model' => $vehicle['body_type_model'] ?? null,
            'or_number' => $vehicle['or_number'] ?? null,
            'cr_number' => $vehicle['cr_number'] ?? null,
        ];

        if (Schema::hasColumn('vehicles', 'rfid_tags')) {
            $vehicleData['rfid_tags'] = json_encode($tags);
        }

        Vehicle::create($vehicleData);
    }


            // activity log
            $adminId = Auth::guard('admin')->id();
            if (!$adminId) {
                abort(403, 'Admin not authenticated');
            }

            ActivityLog::create([
                'actor_type' => 'admin',
                'actor_id' => $adminId,
                'action' => 'create',
                'details' => 'Admin ' . Auth::guard('admin')->user()->firstname . ' ' . Auth::guard('admin')->user()->lastname . " created user {$user->firstname} {$user->lastname}.",
            ]);
        });

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
            'compressedProfilePicture',
            'vehicles',
            'year_section',
            'address',
            'contact_number',
            'expiration_date',
        ]);

        // Reset to one empty vehicle row
        $this->vehicles = [$this->defaultVehicle()];

    }

    public function addRfidTag(int $vehicleIndex)
    {
        // ensure vehicles array exists and has the rfid_tags array
        if (!isset($this->vehicles[$vehicleIndex])) {
            return;
        }

        if (!isset($this->vehicles[$vehicleIndex]['rfid_tags']) || !is_array($this->vehicles[$vehicleIndex]['rfid_tags'])) {
            $this->vehicles[$vehicleIndex]['rfid_tags'] = [''];
        }

        // cap at 3 tags
        if (count($this->vehicles[$vehicleIndex]['rfid_tags']) >= 3) {
            return;
        }

        $this->vehicles[$vehicleIndex]['rfid_tags'][] = '';
    }

    public function removeRfidTag(int $vehicleIndex, int $tagIndex)
    {
        if (!isset($this->vehicles[$vehicleIndex]['rfid_tags'][$tagIndex])) {
            return;
        }

        // remove the tag and reindex
        array_splice($this->vehicles[$vehicleIndex]['rfid_tags'], $tagIndex, 1);

        // ensure there's always at least an empty slot for tag 0 (if you want that)
        if (empty($this->vehicles[$vehicleIndex]['rfid_tags'])) {
            $this->vehicles[$vehicleIndex]['rfid_tags'] = [''];
        }
    }

    public function render()
    {
        return view('livewire.user.edit-profile-component');
    }
}
