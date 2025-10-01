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

    // track if this area originally had car slots (to determine if prefix is editable)
    public $originallyHadCarSlots = false;

    protected $listeners = ['openEditAreaModal' => 'loadArea'];

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
    }

    // Set motorcycleSlots to 0 when checkbox is disabled
    public function updatedMotorcycleEnabled($value)
    {
        if (! $value) {
            $this->motorcycleSlots = 0;
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
        $this->validate([
            'areaName' => 'required|string|max:100',
            'slotPrefix' => $this->carSlotsEnabled && $this->carSlots > 0 ? 'required|string|max:1|regex:/^[A-Z]$/' : '',
        ]);

        $area = ParkingArea::findOrFail($this->areaId);

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

                // Safety: don't allow target below currently occupied (should be caught earlier)
                if ($target < $occupied) {
                    $this->addError('motorcycleSlots', "Cannot set total available to {$target}. Currently {$occupied} motorcycle(s) are parked.");

                    return;
                }

                // Compute new available by preserving occupied motorcycles
                $newAvailable = $target - $occupied;
                if ($newAvailable < 0) {
                    $newAvailable = 0;
                }

                $mc->update([
                    'total_available' => $target,
                    'available_count' => $newAvailable,
                ]);
            } else {
                // create fresh motorcycleCount record
                $area->motorcycleCount()->create([
                    'total_available' => $target,
                    'available_count' => $target,
                ]);
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
        // start position for numeric part (prefix is one letter in your UI)
        $substrPos = strlen($this->slotPrefix) + 1; // usually 2

        // Find any occupied slots whose numeric suffix > targetCount
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
            $currentlyInUse = $mc->total_available - $mc->available_count;

            // Prevent reducing below number currently parked (in-use)
            if ($targetCount < $currentlyInUse) {
                return [
                    'valid' => false,
                    'message' => "Cannot reduce motorcycle slots to {$targetCount}. Currently {$currentlyInUse} motorcycle(s) are parked.",
                ];
            }

            // Prevent reducing below the currently available count (would make available_count > total_available)
            if ($targetCount < $mc->available_count) {
                return [
                    'valid' => false,
                    'message' => "Cannot set total available to {$targetCount}. There are currently {$mc->available_count} available motorcycle slot(s).",
                ];
            }
        }

        return ['valid' => true];
    }

    private function updateCarSlots($area)
    {
        $targetCount = (int) $this->carSlots;
        $currentSlots = $area->carSlots()->orderBy('label')->get();
        $currentCount = $currentSlots->count();

        if ($targetCount > $currentCount) {
            // Add new slots
            $slotsToAdd = $targetCount - $currentCount;
            $newSlots = [];

            for ($i = $currentCount + 1; $i <= $targetCount; $i++) {
                $newSlots[] = [
                    'area_id' => $area->id,
                    'label' => $this->slotPrefix.$i,
                    'occupied' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Bulk insert new slots
            \DB::table('car_slots')->insert($newSlots); // Adjust table name as needed

        } elseif ($targetCount < $currentCount) {
    // Remove slots whose numeric index is greater than targetCount
    $substrPos = strlen($this->slotPrefix) + 1;

    $area->carSlots()
        ->whereRaw("CAST(SUBSTRING(label, {$substrPos}) AS UNSIGNED) > ?", [$targetCount])
        ->delete();
}

        // If count is the same, do nothing (preserve all existing data)
    }

    public function render()
    {
        return view('livewire.admin.edit-area-modal');
    }
}
