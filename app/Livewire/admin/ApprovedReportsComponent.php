<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\Violation;
use App\Models\Vehicle;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class ApprovedReportsComponent extends Component
{
    use WithPagination;
    use WithFileUploads;

    public $violationsActionTaken = [];
    public $vehicles = [];
    public $proofs = []; // holds per-row UploadedFile instances (wire:model="proofs.{id}")
    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        // Load initial vehicles
        $this->vehicles = Vehicle::with('user')
            ->latest()
            ->limit(6)
            ->get()
            ->map(function ($vehicle) {
                return [
                    'id' => $vehicle->id,
                    'license_plate' => $vehicle->license_plate,
                    'user_id' => $vehicle->user_id,
                    'owner_name' => $vehicle->user ? $vehicle->user->firstname . ' ' . $vehicle->user->lastname : null
                ];
            });
    }

    public function findViolatorByPlate($licensePlate)
    {
        if (empty($licensePlate)) {
            return null;
        }

        // Only exact match
        $vehicle = Vehicle::where('license_plate', trim($licensePlate))
            ->with('user')
            ->first();

        if ($vehicle && $vehicle->user) {
            return [
                'user_id' => (string) $vehicle->user->id,
                'owner_name' => trim($vehicle->user->firstname . ' ' . $vehicle->user->lastname),
                'license_plate' => $vehicle->license_plate,
                'vehicle_id' => $vehicle->id
            ];
        }

        return null;
    }

    public function render()
    {
        $violations = Violation::with(['reporter', 'area', 'violator'])
            ->where('status', 'approved') // ✅ only approved
            ->paginate(10); // 10 items per page

        // Process violations for display
        $violations->getCollection()->transform(function ($violation) {
            // Populate missing violator_id from license_plate
            if (empty($violation->violator_id) && !empty($violation->license_plate)) {
                $match = $this->findViolatorByPlate($violation->license_plate);
                if ($match) {
                    $violation->violator_id = $match['user_id'];
                    $violation->save();
                }
            }

            // Populate missing license_plate from violator_id
            if (!empty($violation->violator_id) && empty($violation->license_plate)) {
                $match = $this->findPlatesByViolator($violation->violator_id);
                if ($match && !empty($match['plates'])) {
                    $violation->license_plate = $match['plates'][0];
                    $violation->save();
                }
            }

            // Add virtual property for the view
            $violation->violator_name = $violation->violator
                ? trim($violation->violator->firstname . ' ' . $violation->violator->lastname)
                : 'Unknown';

            return $violation;
        });

        return view('livewire.admin.approved-reports-component', [
            'violations' => $violations
        ]);
    }

    /**
     * Mark a violation as ForEndorsement, optionally store approved image and action_taken.
     * Uses $this->proofs[$violationId] as upload from the row's file input.
     */
    public function markForEndorsement($violationId)
    {
        $violation = Violation::find($violationId);
        if (! $violation) return;

        // validate the optional approved image (per-row)
        $this->validate([
            "proofs.{$violationId}" => 'nullable|image|mimes:jpg,jpeg,png|max:6144', // 6MB
        ]);

        // Load existing evidence safely (support casted array or raw JSON/string)
        $existing = $violation->evidence;
        if (is_string($existing) && $existing !== '') {
            $decoded = @json_decode($existing, true);
            $evidence = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];
        } elseif (is_array($existing)) {
            $evidence = $existing;
        } else {
            $evidence = [];
        }

        // If there's an uploaded approved image for this violation, store it
        if (! empty($this->proofs[$violationId])) {
            try {
                $file = $this->proofs[$violationId];
                $ext = $file->getClientOriginalExtension();
                $hash = substr(md5(uniqid((string) mt_rand(), true)), 0, 8);
                $filename = 'evidence_approved_' . $violationId . '_' . $hash . '.' . $ext;

                // store under storage/app/public/evidence/approved
                $path = $file->storeAs('evidence/approved', $filename, 'public');

                // set approved path (preserve reported if present)
                $evidence['approved'] = $path;
            } catch (\Exception $e) {
                \Log::error('Failed to store approved evidence', ['error' => $e->getMessage()]);
                $this->dispatchBrowserEvent('notify', ['type' => 'error', 'message' => 'Failed to store approved image.']);
                return;
            }
        }

        // Save action taken if provided
        $actionTaken = $this->violationsActionTaken[$violationId] ?? null;
        if ($actionTaken) {
            $violation->action_taken = $actionTaken;
        }

        // Save evidence properly respecting model casts
        $casts = $violation->getCasts();
        if (isset($casts['evidence']) && $casts['evidence'] === 'array') {
            $violation->evidence = $evidence;
        } else {
            $violation->evidence = json_encode($evidence);
        }

        $violation->status = 'for_endorsement';
        $violation->save();
        // determine admin user using the admin guard
$admin = Auth::guard('admin')->user();

// If you require an authenticated admin, you can abort or return with an error:
// if (! $admin) { abort(403, 'Admin not authenticated'); }

$adminName = $admin ? ($admin->firstname ?? $admin->name ?? 'Admin#'.$admin->id) : 'System';
$admin = Auth::guard('admin')->user();

        // optional: create activity log entry for audit
        ActivityLog::create([
            'actor_type' => 'admin',
            'actor_id'   => $admin->admin_id, 
            'area_id' => $violation->area_id,
            'action' => 'resolve',
            'details'    => "Violation #{$violation->id} marked for endorsement by admin {$adminName}",
            'created_at' => now(),
        ]);

        // clear the temporary file in Livewire so UI resets
        if (isset($this->proofs[$violationId])) {
            unset($this->proofs[$violationId]);
        }

        // clear validation for that input
        $this->resetValidation("proofs.$violationId");

        // Reset pagination (optional)
        $this->resetPage();

        session()->flash('message', 'Violation marked as for endorsement and evidence attached.');
    }

    // Add method if it doesn't exist
    private function findPlatesByViolator($violatorId)
    {
        $vehicles = Vehicle::where('user_id', $violatorId)->pluck('license_plate')->toArray();
        return $vehicles ? ['plates' => $vehicles] : null;
    }
}
