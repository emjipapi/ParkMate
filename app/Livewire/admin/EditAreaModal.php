<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\ParkingArea;
use App\Models\CarSlot;

class EditAreaModal extends Component
{
    public $areaId;
    public $areaName;
    public $slotPrefix;
    public $carSlots;
    public $motorcycleSlots;
    public $generatedSlots = [];

    // Updated for Livewire v3
    protected $listeners = ['openEditAreaModal' => 'loadArea'];

    public function loadArea($areaId)
    {
        $this->resetValidation();
        $this->areaId = $areaId;

        // Prefer Eloquent model (object). If your caller passed an array 'id', this will fetch the model.
        $area = ParkingArea::with(['carSlots', 'motorcycleCount'])->find($areaId);

        if (! $area) {
            $this->addError('area', 'Parking area not found.');
            return;
        }

        // area is now an Eloquent model. Still be defensive about relationship shapes.
        $this->areaName = $area->name ?? '';
        // ensure carSlots is a collection (works if relationship returns array or collection)
        $carSlots = collect($area->carSlots ?? []);
        $this->carSlots = $carSlots->count();

        // Read first slot label defensively (handle model or array)
        $first = $carSlots->first();
        $firstLabel = null;
        if ($first) {
            if (is_array($first)) {
                $firstLabel = $first['label'] ?? null;
            } elseif (is_object($first)) {
                $firstLabel = $first->label ?? null;
            }
        }

        $this->slotPrefix = $firstLabel ? strtoupper(substr($firstLabel, 0, 1)) : null;

        // motorcycleCount relationship may be missing or an array; use optional()
        $this->motorcycleSlots = optional($area->motorcycleCount)->total_available ?? 0;

        // Generate C1, C2...
        if ($this->slotPrefix && $this->carSlots > 0) {
            $this->generatedSlots = collect(range(1, $this->carSlots))
                ->map(fn($i) => $this->slotPrefix . $i)
                ->toArray();
        } else {
            $this->generatedSlots = [];
        }
    }

    public function updateArea()
    {
        $this->validate([
            'areaName' => 'required|string|max:255',
        ]);

        $area = ParkingArea::findOrFail($this->areaId);
        $area->update([
            'name' => $this->areaName,
        ]);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Parking area updated successfully!'
        ]);

        // Use js() to hide the modal directly
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