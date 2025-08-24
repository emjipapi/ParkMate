<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;

class ParkingSlotsComponent extends Component
{
    public string $filter = 'all';

    /** @var array<int, array> */
    public array $areas = [];

    public function mount()
    {
        $this->loadAreasData();
    }

    private function loadAreasData()
    {
        // Fetch all parking areas
        $areas = DB::table('parking_areas')->get();

        $this->areas = $areas->map(function ($area) {
            // Motorcycle availability
            $motoAvailable = DB::table('motorcycle_counts')
                ->where('area_id', $area->id)
                ->value('available') ?? 0;

            // Car slots for this area
            $carSlots = DB::table('car_slots')
                ->where('area_id', $area->id)
                ->select('id', 'label', 'occupied')
                ->get()
                ->map(function ($slot) {
                    return [
                        'id' => $slot->id,
                        'label' => $slot->label,
                        'occupied' => (bool) $slot->occupied,
                    ];
                })
                ->toArray();

            // Car slot counts
            $carTotal = count($carSlots);
            $carAvailable = collect($carSlots)->where('occupied', false)->count();

            return [
                'id' => $area->id,
                'name' => $area->name,
                'moto_total' => $area->moto_total,
                'moto_available' => $motoAvailable,
                'car_total' => $carTotal,
                'car_available' => $carAvailable,
                'car_slots' => $carSlots,
            ];
        })->toArray();
    }

    public function refreshSlotData()
    {
        $this->loadAreasData();
    }

    public function incrementMoto(int $areaId)
    {
        DB::table('motorcycle_counts')
            ->where('area_id', $areaId)
            ->increment('available');

        $this->loadAreasData();
    }

    public function decrementMoto(int $areaId)
    {
        DB::table('motorcycle_counts')
            ->where('area_id', $areaId)
            ->where('available', '>', 0)
            ->decrement('available');

        $this->loadAreasData();
    }

    public function openSlot(int $areaId, int $slotId)
    {
        // Optional: open modal or emit event
        // $this->emit('showSlotModal', $areaId, $slotId);
    }

    public function render()
    {
        return view('livewire.parking-slots-component');
    }
}
