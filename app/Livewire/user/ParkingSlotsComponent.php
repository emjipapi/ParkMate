<?php

namespace App\Livewire\User;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // 👈 Add this
use App\Models\ParkingMap;

class ParkingSlotsComponent extends Component
{
    public string $filter = 'all';

    /** @var array<int, array> */
    public array $areas = [];
    public ?int $defaultMapId = null;

    public function mount()
    {
        $this->loadAreasData();
        $this->defaultMapId = ParkingMap::where('is_default', true)->value('id');
    }

    private function loadAreasData()
    {
        Log::info('Loading parking areas data...'); // 👈 log when loading

        // Fetch all parking areas (excluding soft-deleted)
        $areas = DB::table('parking_areas')->whereNull('deleted_at')->get();

        $this->areas = $areas->map(function ($area) {
            // Motorcycle availability
            $motoAvailableCount = DB::table('motorcycle_counts')
                ->where('area_id', $area->id)
                ->value('available_count') ?? 0;

            $motoTotalCount = DB::table('motorcycle_counts')
                ->where('area_id', $area->id)
                ->value('total_available') ?? 0;

            // Calculate occupied count (total - available)
            $motoOccupiedCount = $motoTotalCount - $motoAvailableCount;

            Log::info('Area data loaded', [
                'area_id' => $area->id,
                'area_name' => $area->name,
                'moto_total' => $motoTotalCount,
                'moto_available' => $motoAvailableCount,
                'moto_occupied' => $motoOccupiedCount,
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
                'moto_occupied_count' => $motoOccupiedCount,
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
        Log::info('Slot opened', ['area_id' => $areaId, 'slot_id' => $slotId]);
        // Optional: open modal or emit event
        // $this->emit('showSlotModal', $areaId, $slotId);
    }

    public function render()
    {
        return view('livewire.user.parking-slots-component');
    }
}
