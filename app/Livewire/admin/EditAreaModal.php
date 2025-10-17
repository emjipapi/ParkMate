<?php

namespace App\Livewire\Admin;

use App\Models\ParkingArea;
use Livewire\Component;

class EditAreaModal extends Component
{
    public $areaId;
    public $areaName;
    public $slotPrefix;
    public $carSlots;
    public $motorcycleSlots;
    public $generatedSlots = [];

    // booleans for checkboxes
    public $carSlotsEnabled = false;
    public $motorcycleEnabled = false;

    // User type permissions
    public $allowStudents = false;
    public $allowEmployees = false;
    public $allowGuests = false;

    // track if this area originally had car slots (to determine if prefix is editable)
    public $originallyHadCarSlots = false;
    public $isLocked = false;

    protected $listeners = [
        'openEditAreaModal' => 'loadArea',
        'confirmDeleteArea' => 'deleteArea',
    ];

    public function loadArea($areaId)
    {
        $this->resetValidation();
        $this->areaId = $areaId;

        $area = ParkingArea::with(['carSlots', 'motorcycleCount'])->find($areaId);

        if (! $area) {
            $this->addError('area', 'Parking area not found.');
            return;
        }

        $this->areaName = $area->name ?? '';

        // car slots as collection/array (works with Eloquent or fallback arrays)
        $carSlots = collect($area->carSlots ?? []);
        $this->carSlots = $carSlots->count();

        // track if area originally had car slots
        $this->originallyHadCarSlots = $this->carSlots > 0;

        // checkbox state: enabled if there are car slots
        $this->carSlotsEnabled = $this->carSlots > 0;

        // first car slot label (prefix)
        $first = $carSlots->first();
        $firstLabel = null;
        if ($first) {
            if (is_array($first)) {
                $firstLabel = $first['label'] ?? null;
            } elseif (is_object($first)) {
                $firstLabel = $first->label ?? null;
            }
        }
        $this->slotPrefix = $firstLabel ? strtoupper(substr($firstLabel, 0, 1)) : '';

        $this->motorcycleSlots = optional($area->motorcycleCount)->total_available ?? 0;
        $this->motorcycleEnabled = ($this->motorcycleSlots > 0);

        // Load user type permissions
        $this->allowStudents = (bool) $area->allow_students;
        $this->allowEmployees = (bool) $area->allow_employees;
        $this->allowGuests = (bool) $area->allow_guests;

        // generated slots list
        $this->updateGeneratedSlots();
    }

    // Set carSlots to 0 when checkbox is disabled and update generated slots
    public function updatedCarSlotsEnabled($value)
    {
        if (! $value) {
            $this->carSlots = 0;
        } else {
            // When enabling car slots for an area that originally had none,
            // clear the prefix so user can enter a new one
            if (! $this->originallyHadCarSlots && empty($this->slotPrefix)) {
                $this->slotPrefix = '';
            }
        }
        $this->updateGeneratedSlots();

        // Clear the both-disabled server error as soon as one is enabled
        if ($value || $this->motorcycleEnabled) {
            $this->resetErrorBag('area_flags');
        }
    }

    // Set motorcycleSlots to 0 when checkbox is disabled
    public function updatedMotorcycleEnabled($value)
    {
        if (! $value) {
            $this->motorcycleSlots = 0;
        }

        // Clear the both-disabled server error as soon as one is enabled
        if ($value || $this->carSlotsEnabled) {
            $this->resetErrorBag('area_flags');
        }
    }

    // Update generated slots when carSlots changes (real-time as user types)
    public function updatedCarSlots($value)
    {
        // Ensure we have a valid number
        $this->carSlots = max(0, (int) $value);
        $this->updateGeneratedSlots();
    }

    // Also update when slot prefix changes
    public function updatedSlotPrefix()
    {
        $this->updateGeneratedSlots();
    }

    // Helper method to update generated slots
    private function updateGeneratedSlots()
    {
        // Only generate if we have a prefix, car slots > 0, and car slots are enabled
        if ($this->slotPrefix && $this->carSlots > 0 && $this->carSlotsEnabled) {
            $this->generatedSlots = collect(range(1, (int) $this->carSlots))
                ->map(fn ($i) => $this->slotPrefix.$i)
                ->toArray();
        } else {
            $this->generatedSlots = [];
        }
    }

    public function updateArea()
    {
        // SERVER-SIDE GUARD: do not allow saving when both vehicle types are disabled
        if (! $this->carSlotsEnabled && ! $this->motorcycleEnabled) {
            $this->addError('area_flags', 'Please enable at least one slot type: Car Slots or Motorcycles.');
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Enable at least one slot type before saving.'
            ]);
            return;
        }

        // Check that at least one user type is allowed
        if (!$this->allowStudents && !$this->allowEmployees && !$this->allowGuests) {
            $this->addError('allowStudents', 'You must allow at least one user type (Students, Employees, or Guests).');
            return;
        }

        $this->validate([
            'areaName' => 'required|string|max:100',
            'slotPrefix' => $this->carSlotsEnabled && $this->carSlots > 0 ? 'required|string|max:1|regex:/^[A-Z]$/' : '',
        ]);

        $area = ParkingArea::findOrFail($this->areaId);

        // Only check occupied/disabled slots if we're REDUCING the car slot count
        if ($this->carSlotsEnabled && $this->carSlots > 0) {
            $currentCount = $area->carSlots()->count();
            $targetCount = (int) $this->carSlots;
            
            if ($targetCount < $currentCount) {
                // User is reducing slots, check if any occupied/disabled slots would be affected
                $substrPos = strlen($this->slotPrefix) + 1;
                
                // Find occupied or disabled slots that would be removed (>= instead of >)
                $affectedSlots = $area->carSlots()
                    ->whereRaw("CAST(SUBSTRING(label, {$substrPos}) AS UNSIGNED) >= ?", [$targetCount + 1])
                    ->where(function ($query) {
                        $query->where('occupied', 1)
                              ->orWhere('disabled', 1);
                    })
                    ->get();
                
                if ($affectedSlots->count() > 0) {
                    $labels = $affectedSlots->pluck('label')->join(', ');
                    $this->addError('carSlots', "Cannot reduce slots. These slots are occupied or disabled and would be removed: {$labels}");
                    return;
                }
            }
        }

        // Validate car slots before making changes
        if ($this->carSlotsEnabled && $this->carSlots > 0) {
            $validationResult = $this->validateCarSlotsChange($area);
            if (! $validationResult['valid']) {
                $this->addError('carSlots', $validationResult['message']);
                return;
            }
        }

        // Validate motorcycle slots before making changes
        if ($this->motorcycleEnabled && $this->motorcycleSlots > 0) {
            $validationResult = $this->validateMotorcycleSlotsChange($area);
            if (! $validationResult['valid']) {
                $this->addError('motorcycleSlots', $validationResult['message']);
                return;
            }
        }

        // If validation passes, proceed with updates
        $area->update([
            'name' => $this->areaName,
            'allow_students' => $this->allowStudents,
            'allow_employees' => $this->allowEmployees,
            'allow_guests' => $this->allowGuests,
        ]);

        // Handle car slots updates
        if ($this->carSlotsEnabled && $this->carSlots > 0) {
            $this->updateCarSlots($area);
        } else {
            // Check if we can remove all car slots (none should be occupied)
            $occupiedCount = $area->carSlots()->where('occupied', 1)->count();
            if ($occupiedCount > 0) {
                $this->addError('carSlotsEnabled', "Cannot disable car slots. {$occupiedCount} slot(s) are currently occupied.");
                return;
            }
            $area->carSlots()->delete();
        }

        // Handle motorcycle counts
        if ($this->motorcycleEnabled && $this->motorcycleSlots > 0) {
            $mc = $area->motorcycleCount()->first();
            $target = (int) $this->motorcycleSlots;

            if ($mc) {
                $oldTotal = (int) $mc->total_available;
                $oldAvailable = (int) $mc->available_count;
                $occupied = $oldTotal - $oldAvailable;

                if ($target < $occupied) {
                    $this->addError('motorcycleSlots', "Cannot set total available to {$target}. Currently {$occupied} motorcycle(s) are parked.");
                    return;
                }

                $newAvailable = $target - $occupied;
                if ($newAvailable < 0) {
                    $newAvailable = 0;
                }

                $mc->update([
                    'total_available' => $target,
                    'available_count' => $newAvailable,
                ]);
            } else {
                $area->motorcycleCount()->create([
                    'total_available' => $target,
                    'available_count' => $target,
                ]);
            }
        } else {
            // Disabled or zero target: remove motorcycleCount record (if safe)
            $mc = $area->motorcycleCount()->first();
            if ($mc) {
                $occupied = max(0, (int)$mc->total_available - (int)$mc->available_count);

                if ($occupied > 0) {
                    // Prevent disabling if motorcycles are parked
                    $this->addError('motorcycleEnabled', "Cannot disable motorcycle parking. {$occupied} motorcycle(s) are currently parked.");
                    return;
                }

                // safe to delete (or you could set totals to 0 instead)
                $mc->delete();
            }
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Parking area updated successfully!',
        ]);

        $this->js("
            const modalEl = document.getElementById('editAreaModal');
            if (modalEl) {
                const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
                bsModal.hide();
            }
        ");
    }

    private function validateCarSlotsChange($area)
    {
        $targetCount = (int) $this->carSlots;
        $currentSlots = $area->carSlots()->orderBy('label')->get();
        $currentCount = $currentSlots->count();

        if ($targetCount < $currentCount) {
            $substrPos = strlen($this->slotPrefix) + 1;

            $occupiedSlotsInRange = $area->carSlots()
                ->whereRaw("CAST(SUBSTRING(label, {$substrPos}) AS UNSIGNED) > ?", [$targetCount])
                ->where('occupied', 1)
                ->orderByRaw("CAST(SUBSTRING(label, {$substrPos}) AS UNSIGNED) DESC")
                ->get();

            if ($occupiedSlotsInRange->count() > 0) {
                $occupiedLabels = $occupiedSlotsInRange->pluck('label')->join(', ');

                return [
                    'valid' => false,
                    'message' => "Cannot reduce car slots. These slots are currently occupied: {$occupiedLabels}",
                ];
            }
        }

        return ['valid' => true];
    }

private function validateMotorcycleSlotsChange($area)
{
    $targetCount = (int) $this->motorcycleSlots;
    $mc = $area->motorcycleCount()->first();

    if ($mc) {
        // number of motorcycles currently parked
        $currentlyInUse = max(0, (int)$mc->total_available - (int)$mc->available_count);

        // Only block if the target is less than the number currently parked
        if ($targetCount < $currentlyInUse) {
            return [
                'valid' => false,
                'message' => "Cannot reduce motorcycle slots to {$targetCount}. Currently {$currentlyInUse} motorcycle(s) are parked.",
            ];
        }

        // No need to check $mc->available_count here — updateArea()
        // will recompute available_count = target - occupied safely.
    }

    return ['valid' => true];
}


    private function updateCarSlots($area)
    {
        $targetCount = (int) $this->carSlots;
        $currentSlots = $area->carSlots()->orderBy('label')->get();
        $currentCount = $currentSlots->count();

        if ($targetCount > $currentCount) {
            $newSlots = [];

            for ($i = $currentCount + 1; $i <= $targetCount; $i++) {
                $newSlots[] = [
                    'area_id' => $area->id,
                    'label' => $this->slotPrefix.$i,
                    'occupied' => false,
                    'disabled' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            \DB::table('car_slots')->insert($newSlots);

        } elseif ($targetCount < $currentCount) {
            $substrPos = strlen($this->slotPrefix) + 1;

            $area->carSlots()
                ->whereRaw("CAST(SUBSTRING(label, {$substrPos}) AS UNSIGNED) > ?", [$targetCount])
                ->delete();
        }
    }

    public function deleteArea()
    {
        $area = ParkingArea::with(['carSlots', 'motorcycleCount'])->find($this->areaId);
        if (! $area) {
            $this->addError('area', 'Parking area not found.');
            return;
        }

        // Check occupied and disabled car slots
        $occupiedCars = $area->carSlots()->where('occupied', 1)->count();
        $disabledCars = $area->carSlots()->where('disabled', 1)->count();
        
        if ($occupiedCars > 0 || $disabledCars > 0) {
            $this->addError('delete', "Cannot delete area. {$occupiedCars} car(s) are occupied and {$disabledCars} car(s) are disabled.");
            return;
        }

        // Check occupied motorcycles (if motorcycleCount exists)
        $mc = $area->motorcycleCount()->first();
        $occupiedMotorcycles = 0;
        if ($mc) {
            $occupiedMotorcycles = max(0, (int)$mc->total_available - (int)$mc->available_count);
        }

        if ($occupiedMotorcycles > 0) {
            $this->addError('delete', "Cannot delete area. {$occupiedMotorcycles} motorcycle(s) are currently parked.");
            return;
        }

        $deletedId = $area->id;
        $area->delete();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Parking area deleted successfully!',
        ]);

        $this->dispatch('areaDeleted', $deletedId);

        $this->js("
            const modalEl = document.getElementById('editAreaModal');
            if (modalEl) {
                const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
                bsModal.hide();
            }
        ");
    }

    public function render()
    {
        return view('livewire.admin.edit-area-modal');
    }
}