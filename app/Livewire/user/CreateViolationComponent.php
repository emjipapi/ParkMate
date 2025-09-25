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
            \Log::info('📥 File upload detected — starting compression process...');

            $hash = substr(md5(uniqid(rand(), true)), 0, 8);
            $filename = 'evidence_' . auth()->id() . '_' . $hash . '.jpg';

            \Log::info('⚙️ Compression activated. Processing image: ' . $this->evidence->getClientOriginalName());

            $image = Image::read($this->evidence->getPathname())
                ->scaleDown(1200, 1200)
                ->toJpeg(90);

            $path = 'evidence/tmp/' . $filename;
            Storage::disk('public')->put($path, $image);

            // ✅ store compressed file path separately
            $this->compressedEvidence = $path;

            \Log::info('✅ Compression finished successfully. Saved to: ' . $path);
        } catch (\Exception $e) {
            \Log::error('❌ Failed to process evidence image on upload: ' . $e->getMessage());
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
    // Validate FIRST, before processing files
    $this->validate([
        'description' => 'required|string',
        'area_id' => 'required|exists:parking_areas,id',
        'evidence' => 'nullable|file|mimes:jpg,jpeg,png|max:10240', // 10MB
        'license_plate' => 'nullable|string|max:255',
        'violator' => 'nullable|integer|exists:users,id',
    ]);

    // Process the evidence file AFTER validation
    $evidencePath = null;
if ($this->compressedEvidence) {
    $finalPath = str_replace('tmp/', 'reported/', $this->compressedEvidence);
    Storage::disk('public')->move($this->compressedEvidence, $finalPath);
    $evidencePath = $finalPath;
}



    // Get the correct description
    $desc = $this->description === "Other" ? $this->otherDescription : $this->description;
    
    $evidenceData = [
        'reported' => $evidencePath, // your uploaded file
        'approved' => null,           // will be set later when approved
    ];

    // Create the violation record
    Violation::create([
        'reporter_id' => auth()->id(),
        'description' => $desc,
        'evidence' => $evidenceData, // Remove json_encode since model casts it as array
        'area_id' => $this->area_id,
        'license_plate' => strtoupper(trim($this->license_plate)),
        'violator_id' => $this->violator,
        'status' => 'pending',
        'submitted_at' => now(), // Add this timestamp
    ]);

    // Build details string
    $userName = auth()->user()->firstname . ' ' . auth()->user()->lastname;
    $details = "User {$userName} submitted a violation report";
    if (!empty($this->license_plate)) {
        $details .= " for plate {$this->license_plate}";
    }
    $details .= ".";

    // Log the activity
    ActivityLog::create([
        'actor_type' => 'user',
        'actor_id' => auth()->id(),
        'area_id' => $this->area_id,
        'action' => 'report',
        'details' => $details,
        'created_at' => now(),
    ]);

    session()->flash('success', 'Report submitted successfully!');
    return redirect()->route('user.violation.tracking');
}

    public function render()
    {
        return view('livewire.user.create-violation-component', [
            'areas' => $this->areas
        ]);
    }
}