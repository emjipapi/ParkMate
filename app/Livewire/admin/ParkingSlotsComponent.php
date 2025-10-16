<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // 👈 Add this

class ParkingSlotsComponent extends Component
{
    public string $filter = 'all';

    /** @var array<int, array> */
    public array $areas = [];

    public function mount()
    {
        $this->loadAreasData();
    }
    // Fixed method using dispatch() - this is the proper Livewire v3 way
    public function openEditAreaModalServer($areaId)
    {
        // First, dispatch to the EditAreaModal component to load the area data
        $this->dispatch('openEditAreaModal', areaId: $areaId);
        
        // Then use js() for a simple JavaScript call to show the modal
        $this->js("
            setTimeout(() => {
                const modalEl = document.getElementById('editAreaModal');
                if (modalEl) {
                    const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    bsModal.show();
                }
            }, 100);
        ");
    }
    private function loadAreasData()
    {
        Log::info('Loading parking areas data...'); // 👈 log when loading

        // Fetch all parking areas
        $areas = DB::table('parking_areas')->get();

        $this->areas = $areas->map(function ($area) {
            // Motorcycle availability
            $motoAvailableCount = DB::table('motorcycle_counts')
                ->where('area_id', $area->id)
                ->value('available_count') ?? 0;

            $motoTotalCount = DB::table('motorcycle_counts')
                ->where('area_id', $area->id)
                ->value('total_available') ?? 0;

            Log::info('Area data loaded', [
                'area_id' => $area->id,
                'area_name' => $area->name,
                'moto_total' => $motoTotalCount,
                'moto_available' => $motoAvailableCount,
            ]);

            // Car slots for this area
            $carSlots = DB::table('car_slots')
                ->where('area_id', $area->id)
                ->select('id', 'label', 'occupied', 'disabled')
                ->get()
                ->map(function ($slot) {
                    return [
                        'id' => $slot->id,
                        'label' => $slot->label,
                        'occupied' => (bool) $slot->occupied,
                        'disabled' => (bool) $slot->disabled,
                    ];
                })
                ->toArray();

            // Car slot counts
            $carTotal = count($carSlots);
            $carAvailable = collect($carSlots)->where('occupied', false)->count();

            return [
                'id' => $area->id,
                'name' => $area->name,
                'moto_total' => $motoTotalCount,
                'moto_available_count' => $motoAvailableCount,
                'car_total' => $carTotal,
                'car_available' => $carAvailable,
                'car_slots' => $carSlots,
            ];
        })->toArray();
    }

    public function refreshSlotData()
    {
        Log::info('Refreshing slot data...');
        $this->loadAreasData();
    }

    public function incrementMoto(int $areaId)
    {
        Log::info('Incrementing motorcycle slot', ['area_id' => $areaId]);

        $before = DB::table('motorcycle_counts')->where('area_id', $areaId)->value('available_count');
        Log::info('Before increment', ['area_id' => $areaId, 'available_count' => $before]);

        DB::table('motorcycle_counts')
            ->where('area_id', $areaId)
            ->increment('available_count');

        $after = DB::table('motorcycle_counts')->where('area_id', $areaId)->value('available_count');
        Log::info('After increment', ['area_id' => $areaId, 'available_count' => $after]);

        $this->loadAreasData();
    }

    public function decrementMoto(int $areaId)
    {
        Log::info('Decrementing motorcycle slot', ['area_id' => $areaId]);

        $before = DB::table('motorcycle_counts')->where('area_id', $areaId)->value('available_count');
        Log::info('Before decrement', ['area_id' => $areaId, 'available_count' => $before]);

        DB::table('motorcycle_counts')
            ->where('area_id', $areaId)
            ->where('available_count', '>', 0)
            ->decrement('available_count');

        $after = DB::table('motorcycle_counts')->where('area_id', $areaId)->value('available_count');
        Log::info('After decrement', ['area_id' => $areaId, 'available_count' => $after]);

        $this->loadAreasData();
    }

public function openSlot(int $areaId, int $slotId)
{
    Log::info('Slot toggled', ['area_id' => $areaId, 'slot_id' => $slotId]);

    DB::table('car_slots')
        ->where('id', $slotId)
        ->update(['disabled' => DB::raw('NOT disabled')]);

    $this->loadAreasData();
}

    public function render()
    {
        return view('livewire.admin.parking-slots-component');
    }
}
