<?php

namespace App\Livewire\User;
use App\Models\Violation;
use Livewire\Component;
use App\Models\ParkingArea;
use App\Models\ActivityLog;
use Livewire\WithFileUploads;

class CreateViolationComponent extends Component
{
    use WithFileUploads;

    public $description;
    public $otherDescription;
    public $evidence;
    public $license_plate;
    public $violator;

    public $areas = [];   // list of areas
    public $area_id;      // selected area ID

    public function mount()
    {
        $this->areas = ParkingArea::all(); // store the list here
    }

    public function submitReport()
    {
        // Validate FIRST, before processing files
        $this->validate([
            'description' => 'required|string',
            'area_id' => 'required|exists:parking_areas,id',
            'evidence' => 'nullable|file|mimes:jpg,jpeg,png|max:6144', // 6MB
            'license_plate' => 'nullable|string|max:255',
            'violator' => 'nullable|string|max:255',
        ]);

        // Process the evidence file AFTER validation
$evidencePath = null;
if ($this->evidence) {
    $ext = $this->evidence->getClientOriginalExtension();
    $hash = substr(md5(uniqid(rand(), true)), 0, 8);
    $filename = 'evidence_' . auth()->id() . '_' . $hash . '.' . $ext;

    // Store the file and get the path in public storage
    $evidencePath = $this->evidence->storeAs('evidence', $filename, 'public');
}

        // Get the correct description
        $desc = $this->description === "Other" ? $this->otherDescription : $this->description;

        // Create the violation record
        Violation::create([
            'reporter_id'   => auth()->id(),
            'description'   => $desc,
            'evidence'      => $evidencePath, // Use the stored file path
            'area_id'       => $this->area_id,   // now just the selected ID
            'license_plate' => $this->license_plate,
            'violator'      => $this->violator,
            'status'        => 'pending',
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
        'actor_id'   => auth()->id(),
        'area_id'    => $this->area_id,
        'action'     => 'report',
        'details'    => $details,
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