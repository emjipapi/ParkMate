<?php

namespace App\Livewire\User;
use App\Models\Violation;
use Livewire\Component;
use App\Models\ParkingArea;
use App\Models\ActivityLog;
use Livewire\WithFileUploads;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\Storage;
class CreateViolationComponent extends Component
{
    use WithFileUploads;

    public $description;
    public $otherDescription;
    public $license_plate;
    public $violator;

    public $areas = [];   // list of areas
    public $area_id;      // selected area ID
    public $customArea = '';
    public $evidence;
    public bool $isUploadingEvidence = false;


    public function mount()
    {
        $this->areas = ParkingArea::all(); // store the list here
    }

    public function updatingEvidence()
    {
        $this->isUploadingEvidence = true;
    }

    public function updatedEvidence()
    {
        // When the file finishes uploading, this gets called
        $this->isUploadingEvidence = false;
    }




public function updatedLicensePlate()
{
    if (!empty($this->license_plate)) {
        // Find vehicle with case-insensitive license plate match
        $vehicle = \App\Models\Vehicle::whereRaw('LOWER(license_plate) = LOWER(?)', [trim($this->license_plate)])
            ->with('user')
            ->first();
        
        if ($vehicle && $vehicle->user) {
            $this->violator = $vehicle->user->id;
        } else {
            $this->violator = null; // Clear if no match found
        }
    } else {
        $this->violator = null; // Clear if license plate is empty
    }
}
public function submitReport()
{
    // Check if upload is still in progress
    if ($this->isUploadingEvidence) {
        session()->flash('error', 'Please wait for the image to finish uploading.');
        return;
    }

    // Validate FIRST
    $this->validate([
        'description' => 'required|string',
        'area_id' => 'required|string',
        'customArea' => 'nullable|required_if:area_id,other|string|max:255',
        'license_plate' => 'nullable|string|max:255',
        'evidence' => 'nullable|file|mimes:jpg,jpeg,png|max:10240',
        'violator' => 'nullable|integer|exists:users,id',
    ]);

    // Additional validation: if area_id is not 'other', it must be a valid parking area
    if ($this->area_id !== 'other') {
        if (!is_numeric($this->area_id) || !ParkingArea::find($this->area_id)) {
            $this->addError('area_id', 'Invalid area selected.');
            return;
        }
    }

    // Process the evidence file AFTER validation
    $evidencePath = null;
    if ($this->evidence) {
        $evidencePath = $this->evidence->store('evidence/reported', 'public');
    }

    // Rest of your existing code...
    $desc = $this->description === "Other" ? $this->otherDescription : $this->description;
    
    $evidenceData = [
        'reported' => $evidencePath,
        'approved' => null,
    ];

    $user = auth()->user();
    if (! $user) {
        abort(403, 'User not authenticated');
    }

    $data = [
        'description'   => $desc,
        'evidence'      => $evidenceData,
        'area_id'       => $this->area_id !== 'other' ? (int)$this->area_id : null,
        'custom_area'   => $this->area_id === 'other' ? $this->customArea : null,
        'license_plate' => strtoupper(trim($this->license_plate)),
        'violator_id'   => $this->violator,
        'status'        => 'pending',
        'submitted_at'  => now(),
    ];

    $violation = $user->reportedViolations()->create($data);

$area = $this->area_id !== 'other' ? ParkingArea::find($this->area_id) : null;

$userName = trim(($user->firstname ?? '') . ' ' . ($user->lastname ?? ''));
$licensePlate = !empty($this->license_plate)
    ? strtoupper($this->license_plate)
    : '(empty)';

$areaName = $area
    ? $area->name
    : ($this->customArea ?: '(empty)');

ActivityLog::create([
    'actor_type' => 'user',
    'actor_id'   => $user->getKey(),
    'area_id'    => $this->area_id !== 'other' ? $this->area_id : null,
    'action'     => 'report',
    'details'    => "User {$userName} submitted a violation report "
                  . "with license plate {$licensePlate} in area {$areaName}.",
    'created_at' => now(),
]);


    session()->flash('success', 'Report submitted successfully!');
    $this->resetFormInputs();
}
public function resetFormInputs()
{
    $this->description = null;
    $this->otherDescription = null;
    $this->license_plate = null;
    $this->violator = null;
    $this->area_id = null;
    $this->customArea = null;
    $this->evidence = null;
    $this->isUploadingEvidence = false;
}


    public function render()
    {
        return view('livewire.user.create-violation-component', [
            'areas' => $this->areas
        ]);
    }
}