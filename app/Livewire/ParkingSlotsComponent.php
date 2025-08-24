<?php

// app/Livewire/ParkingSlots.php
namespace App\Livewire;

use Livewire\Component;

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
        // Example shape; replace with real queries (Areas + Slots).
        $this->areas = [
            [
                'id' => 1,
                'name' => 'Talipapa',
                'moto_total' => 20,
                'moto_available' => 12,
                'car_total' => 8,
                'car_available' => 5,
                'car_slots' => [
                    ['id'=>1,'label'=>'C1','occupied'=>true],
                    ['id'=>2,'label'=>'C2','occupied'=>true],
                    ['id'=>3,'label'=>'C3','occupied'=>true],
                    ['id'=>4,'label'=>'C4','occupied'=>true],
                    ['id'=>5,'label'=>'C5','occupied'=>false],
                    ['id'=>6,'label'=>'C6','occupied'=>true],
                    ['id'=>7,'label'=>'C7','occupied'=>false],
                    ['id'=>8,'label'=>'C8','occupied'=>true],
                ],
            ],
            [
                'id' => 2,
                'name' => 'CCS',
                'moto_total' => 69,
                'moto_available' => 5,
                // North Wing has NO car slots at all - completely remove car_slots
            ],
                        [
                'id' => 3,
                'name' => 'Mamamo',
                'moto_total' => 20,
                'moto_available' => 12,
                'car_total' => 8,
                'car_available' => 5,
                'car_slots' => [
                    ['id'=>1,'label'=>'N1','occupied'=>false],
                    ['id'=>2,'label'=>'N2','occupied'=>true],
                    ['id'=>3,'label'=>'N3','occupied'=>true],
                ],
            ],
        ];
    }

    public function refreshSlotData()
    {
        // This method will be called by polling to update slot data
        // without affecting accordion state
        $this->loadAreasData();
        
        // Recalculate availability from sensor data ONLY for areas that have car slots
        foreach ($this->areas as &$area) {
            if (isset($area['car_slots']) && is_array($area['car_slots']) && !empty($area['car_slots'])) {
                $area['car_total'] = count($area['car_slots']);
                $area['car_available'] = collect($area['car_slots'])->where('occupied', false)->count();
            }
            // Don't touch areas without car_slots - leave them as motorcycles-only
        }
    }

    public function incrementMoto(int $areaId)
    {
        // Update DB, then refresh $areas (or just adjust the array)
        foreach ($this->areas as &$a) {
            if ($a['id'] === $areaId && $a['moto_available'] < $a['moto_total']) {
                $a['moto_available']++;
                break;
            }
        }
    }

    public function decrementMoto(int $areaId)
    {
        foreach ($this->areas as &$a) {
            if ($a['id'] === $areaId && $a['moto_available'] > 0) {
                $a['moto_available']--;
                break;
            }
        }
    }

    public function openSlot(int $areaId, int $slotId)
    {
        // Optional: open a modal, show details, force refresh, etc.
        // $this->emit('showSlotModal', $areaId, $slotId);
    }

    public function render()
    {
        // Ensure car counts are up to date each render
        foreach ($this->areas as &$area) {
            // Only calculate car totals if the area actually has car slots
            if (isset($area['car_slots']) && is_array($area['car_slots']) && !empty($area['car_slots'])) {
                $area['car_total'] = count($area['car_slots']);
                $area['car_available'] = collect($area['car_slots'])->where('occupied', false)->count();
            } else {
                // Area has no car slots - ensure these are not set or are 0
                $area['car_total'] = 0;
                $area['car_available'] = 0;
                if (!isset($area['car_slots'])) {
                    $area['car_slots'] = [];
                }
            }
        }
        
        return view('livewire.parking-slots-component');
    }
}