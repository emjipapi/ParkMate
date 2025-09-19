<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Violation;
use App\Models\Vehicle;
use App\Models\User;

class ApprovedReportsComponent extends Component
{
    use WithPagination;

    public $violationsActionTaken = [];
    public $vehicles = [];
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

    public function markResolved($violationId)
    {
        $violation = Violation::find($violationId);

        if (!$violation) return;

        // Save action taken if provided
        $actionTaken = $this->violationsActionTaken[$violationId] ?? null;
        if ($actionTaken) {
            $violation->action_taken = $actionTaken;
        }

        $violation->status = 'resolved';
        $violation->save();

        // Reset pagination to first page after update
        $this->resetPage();

        session()->flash('message', 'Violation marked as resolved.');
    }

    // Add method if it doesn't exist
    private function findPlatesByViolator($violatorId)
    {
        $vehicles = Vehicle::where('user_id', $violatorId)->pluck('license_plate')->toArray();
        return $vehicles ? ['plates' => $vehicles] : null;
    }
}