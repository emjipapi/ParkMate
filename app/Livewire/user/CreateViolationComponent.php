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
    public $evidence;
public $compressedEvidence;


    public function mount()
    {
        $this->areas = ParkingArea::all(); // store the list here
    }


public function updatedEvidence()
{
    if ($this->evidence instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
        try {
            \Log::info('📥 File upload detected — dispatching compression job (user)...');

            // Generate deterministic filename
            $hash = substr(md5(uniqid((string)rand(), true)), 0, 8);
            $userId = auth()->id();
            $filename = 'evidence_user_' . $userId . '_' . $hash . '.jpg';

            // Store original to local disk temporarily
            $tmpOriginalPath = 'evidence/uploads/originals/' . $filename;
            $this->evidence->storeAs('evidence/uploads/originals', $filename, 'local');

            // Set expected compressed path
            $compressedPath = 'evidence/tmp/' . $filename;
            $this->compressedEvidence = $compressedPath;

            \Log::info('⚙️ Compression job dispatched. Input: '.$tmpOriginalPath.' Output: '.$compressedPath);

            // Dispatch job
            \App\Jobs\ProcessEvidenceImage::dispatch(
                'local',                 // input disk
                $tmpOriginalPath,        // input path
                'public',                // output disk
                [$compressedPath],       // output paths array
                1200,                    // maxWidth
                1200,                    // maxHeight
                90                       // quality
            );

        } catch (\Exception $e) {
            \Log::error('❌ Failed to dispatch compression job: ' . $e->getMessage());
            session()->flash('error', 'Failed to process the uploaded image. Please try again.');
            $this->compressedEvidence = null;
        }
    } else {
        \Log::warning('⚠️ updatedEvidence() called, but $this->evidence is not a TemporaryUploadedFile.');
    }
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
    // Validate FIRST
    $this->validate([
        'description' => 'required|string',
        'area_id' => 'required|exists:parking_areas,id',
        'license_plate' => 'nullable|string|max:255',
        'violator' => 'nullable|integer|exists:users,id',
    ]);

    // Process the evidence file AFTER validation
    $evidencePath = null;
    if ($this->compressedEvidence) {
        // Check if file exists (job may still be processing)
        if (Storage::disk('public')->exists($this->compressedEvidence)) {
            $finalPath = str_replace('tmp/', 'reported/', $this->compressedEvidence);
            Storage::disk('public')->move($this->compressedEvidence, $finalPath);
            $evidencePath = $finalPath;
        } else {
            \Log::warning('Compressed evidence not ready yet', [
                'expected_path' => $this->compressedEvidence
            ]);
            session()->flash('error', 'Image is still processing. Please wait a moment and try again.');
            return;
        }
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
        'area_id'       => $this->area_id,
        'license_plate' => strtoupper(trim($this->license_plate)),
        'violator_id'   => $this->violator,
        'status'        => 'pending',
        'submitted_at'  => now(),
    ];

    $violation = $user->reportedViolations()->create($data);

    $userName = trim(($user->firstname ?? '') . ' ' . ($user->lastname ?? ''));
    ActivityLog::create([
        'actor_type' => 'user',
        'actor_id'   => $user->getKey(),
        'area_id'    => $this->area_id,
        'action'     => 'report',
        'details'    => "User {$userName} submitted a violation report" . (!empty($this->license_plate) ? " for plate {$this->license_plate}" : '') . ".",
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
    $this->evidence = null;
    $this->compressedEvidence = null;
}


    public function render()
    {
        return view('livewire.user.create-violation-component', [
            'areas' => $this->areas
        ]);
    }
}