<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Violation;
use App\Models\Vehicle;
use App\Models\User;

class ApprovedReportsComponent extends Component
{
    public $violations;
    public $violationsActionTaken = [];
    public $vehicles = [];


    public function mount()
    {
        $this->refreshViolations();

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


    private function refreshViolations()
{
    $this->violations = Violation::with(['reporter', 'area', 'violator'])
        ->where('status', 'approved') // ✅ only approved
        ->get()
        ->map(function ($violation) {
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

    // ✅ Refresh only approved ones again (this one disappears from list)
    $this->refreshViolations();

    session()->flash('message', 'Violation marked as resolved.');
}

}
