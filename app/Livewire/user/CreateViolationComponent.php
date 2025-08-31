<?php

namespace App\Livewire\User;
use App\Models\Violation;
use Livewire\Component;
use App\Models\ParkingArea;
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
            'evidence' => 'nullable|file|mimes:jpg,jpeg,png,mp4|max:2048', // 10MB max
            'license_plate' => 'nullable|string|max:255',
            'violator' => 'nullable|string|max:255',
        ]);

        // Process the evidence file AFTER validation
        $evidencePath = null;
        if ($this->evidence) {
            $ext = $this->evidence->getClientOriginalExtension();
            $hash = substr(md5(uniqid(rand(), true)), 0, 8);
            $filename = 'evidence_' . auth()->id() . '_' . $hash . '.' . $ext;

            // Store the file and get the path
            $evidencePath = $this->evidence->storeAs('evidence', $filename, 'private');
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
            'status'        => 'Pending',
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