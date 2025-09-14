<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\ParkingArea;
use App\Models\CarSlot;
use App\Models\MotorcycleCount;
use Illuminate\Support\Facades\DB;

class CreateAreaModal extends Component
{
    public $areaName;
    public $carSlots = 0;
    public $motorcycleSlots = 0;
    public $slotPrefix;

    protected $rules = [
        'areaName'        => 'required|string|max:255',
        'carSlots'        => 'nullable|integer|min:0',
        'motorcycleSlots' => 'nullable|integer|min:0',
        'slotPrefix'      => 'nullable|alpha|size:1',
    ];

    public function createArea()
    {
        $this->validate();

        // Convert null/empty values to 0 for processing
        $carSlots = (int)($this->carSlots ?? 0);
        $motorcycleSlots = (int)($this->motorcycleSlots ?? 0);

        // require at least one type of parking slot
        if ($carSlots == 0 && $motorcycleSlots == 0) {
            $this->addError('carSlots', 'You must create at least one car slot or motorcycle slot.');
            $this->addError('motorcycleSlots', 'You must create at least one car slot or motorcycle slot.');
            return;
        }

        // require prefix when creating car slots
        if ($carSlots > 0 && empty($this->slotPrefix)) {
            $this->addError('slotPrefix', 'Slot prefix is required when creating car slots.');
            return;
        }

        // check if slot prefix is already in use
        if ($carSlots > 0 && !empty($this->slotPrefix)) {
            $existingPrefix = CarSlot::where('label', 'LIKE', strtoupper($this->slotPrefix) . '%')->exists();
            if ($existingPrefix) {
                $this->addError('slotPrefix', 'This slot prefix is already in use. Please choose a different letter.');
                return;
            }
        }

        DB::beginTransaction();
        try {
            // 1) create parking area
            $parkingArea = ParkingArea::create([
                'name' => $this->areaName,
            ]);

            // 2) create motorcycle_counts row (use area_id)
            MotorcycleCount::create([
                'area_id'         => $parkingArea->id,
                'total_available' => $motorcycleSlots,
                'available_count' => $motorcycleSlots, // start available equal to total
            ]);

            // 3) create car_slots rows (use area_id)
            if ($carSlots > 0) {
                for ($i = 1; $i <= $carSlots; $i++) {
                    CarSlot::create([
                        'area_id'     => $parkingArea->id,
                        'label'       => strtoupper($this->slotPrefix) . $i, // e.g. D1, D2... (uppercase)
                        'is_occupied' => 0,
                    ]);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->addError('areaName', 'Failed to create parking area: ' . $e->getMessage());
            return;
        }

        // reset and notify parent / UI
        $this->reset(['areaName', 'carSlots', 'motorcycleSlots', 'slotPrefix']);

        // emit Livewire event so parent components can refresh lists
        $this->dispatch('areaCreated');

        // close the bootstrap modal using your existing event listener
        $this->dispatch('close-create-area-modal');

        // optional success notification using your existing event listener
        $this->dispatch('notify', [
            'type' => 'success', 
            'message' => 'Parking area created successfully'
        ]);
    }

    public function render()
    {
        return view('livewire.admin.create-area-modal');
    }
}