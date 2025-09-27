<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Violation;
use App\Models\ParkingArea;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\ActivityLog;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class CreateViolationComponent extends Component
{
    use WithFileUploads;

     public $description;
    public $otherDescription;
    public $license_plate; // keep snake_case
    public $violator;      // user id or null

    public $areas = [];
    public $area_id;
    public $evidence;
    public $compressedEvidence;

    public $violatorStatus = null;
    public $violatorName = null;

    public function mount()
    {
        // ensure admin is authenticated
        if (! Auth::guard('admin')->check()) {
            abort(403);
        }

        $this->areas = ParkingArea::all();
    }

    public function updatedEvidence()
    {
        if ($this->evidence instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
            try {
                \Log::info('📥 File upload detected — starting compression process (admin)...');

                $hash = substr(md5(uniqid(rand(), true)), 0, 8);
                $adminId = Auth::guard('admin')->id();
                $filename = 'evidence_admin_' . $adminId . '_' . $hash . '.jpg';

                \Log::info('⚙️ Compression activated. Processing image: ' . $this->evidence->getClientOriginalName());

                $image = Image::read($this->evidence->getPathname())
                    ->scaleDown(1200, 1200)
                    ->toJpeg(90);

                $path = 'evidence/tmp/' . $filename;
                Storage::disk('public')->put($path, $image);

                // store compressed file path separately (tmp)
                $this->compressedEvidence = $path;

                \Log::info('✅ Compression finished successfully. Saved to: ' . $path);
            } catch (\Exception $e) {
                \Log::error('❌ Failed to process evidence image on upload (admin): ' . $e->getMessage());
                session()->flash('error', 'Failed to process the uploaded image. Please try again.');
                $this->compressedEvidence = null;
            }
        } else {
            \Log::warning('⚠️ updatedEvidence() called, but $this->evidence is not a TemporaryUploadedFile.');
        }
    }


public $violator_id = null;

  public function updatedLicensePlate($value)
    {
        $this->violatorStatus = 'loading';
        $this->violatorName = null;
        $this->violator = null;

        $plate = strtoupper(trim($value));
        if ($plate === '') {
            $this->violatorStatus = null;
            return;
        }

        $vehicle = Vehicle::whereRaw('UPPER(license_plate) = ?', [$plate])
            ->with('user')
            ->first();

        if ($vehicle && $vehicle->user) {
            $this->violatorStatus = 'found';
            $this->violatorName = trim($vehicle->user->firstname . ' ' . $vehicle->user->lastname);
            $this->violator = $vehicle->user->id;
        } else {
            $this->violatorStatus = 'not_found';
            $this->violatorName = null;
            $this->violator = null;
        }
    }

    public function submitReport($status = 'approved')
    {
        // Defensive: re-run lookup (synchronous, server-side) to avoid race conditions
        $plate = strtoupper(trim($this->license_plate ?? ''));
        $vehicle = null;
        if ($plate !== '') {
            $vehicle = Vehicle::whereRaw('UPPER(license_plate) = ?', [$plate])
                              ->with('user')
                              ->first();
        }

        if ($vehicle && $vehicle->user) {
            $this->violator = $vehicle->user->id;
            $this->violatorStatus = 'found';
        } else {
            $this->violator = null;
            $this->violatorStatus = 'not_found';
        }

        // If we require a found violator for 'approved' submissions, block it
        if ($status !== 'pending' && $this->violator === null) {
            session()->flash('error', 'No violator found for that license plate. Please check the plate or submit as pending.');
            return;
        }

        // Validate (violator may be null for pending)
        $this->validate([
            'description' => 'required|string',
            'area_id' => 'required|exists:parking_areas,id',
            'evidence' => 'nullable|file|mimes:jpg,jpeg,png|max:10240',
            'license_plate' => 'nullable|string|max:255',
            'violator' => 'nullable|integer|exists:users,id',
        ]);

        // move compressed evidence if exists
        $evidencePath = null;
        if ($this->compressedEvidence) {
            $finalPath = str_replace('tmp/', 'reported/', $this->compressedEvidence);
            Storage::disk('public')->move($this->compressedEvidence, $finalPath);
            $evidencePath = $finalPath;
        }

        $desc = $this->description === "Other" ? $this->otherDescription : $this->description;
        $evidenceData = [
            'reported' => $status === 'pending' ? $evidencePath : null,
            'approved' => $status === 'approved' ? $evidencePath : null,
        ];

        $admin = Auth::guard('admin')->user();
        if (! $admin) abort(403, 'Admin not authenticated');

        $data = [
            'description'   => $desc,
            'evidence'      => $evidenceData,
            'area_id'       => $this->area_id,
            'license_plate' => $plate ?: null,
            'violator_id'   => $this->violator,
            'status'        => $status,
            'submitted_at'  => now(),
        ];

        $violation = $admin->reportedViolations()->create($data);

        ActivityLog::create([
            'actor_type' => 'admin',
            'actor_id'   => $admin->getKey(),
            'area_id'    => $this->area_id,
            'action'     => 'report',
            'details'    => "Admin {$admin->firstname} created a {$status} violation report" 
                             . (!empty($this->license_plate) ? " for plate {$this->license_plate}" : '') . ".",
            'created_at' => now(),
        ]);

        session()->flash('success', "Report submitted as {$status} successfully!");
    }

    public function render()
    {
        return view('livewire.admin.create-violation-component', [
            'areas' => $this->areas,
        ]);
    }
}
