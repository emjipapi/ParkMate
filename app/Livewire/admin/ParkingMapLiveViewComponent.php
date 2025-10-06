<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\ParkingMap;
use Illuminate\Support\Facades\DB;

class ParkingMapLiveViewComponent extends Component
{
    public $mapId;
    public $map;
    public $areaConfig = [];
    public $areaStatuses = [];

    // poll every 5s from blade
    protected $listeners = [
        'refreshStatuses' => 'refreshStatuses',
    ];

    public function mount($mapId)
    {
        $this->mapId = $mapId;
        $this->loadMap();
        $this->computeStatuses();
    }

    protected function loadMap()
    {
        $this->map = ParkingMap::find($this->mapId);
        if ($this->map) {
            $this->areaConfig = (array) $this->map->area_config ?? [];
        } else {
            $this->areaConfig = [];
        }
    }

    public function refreshStatuses()
    {
        $this->computeStatuses();
    }

    protected function computeStatuses()
    {
        $statuses = [];

        foreach ($this->areaConfig as $areaKey => $cfg) {
            // Normalize config values
            $enabled = !empty($cfg['enabled']);
            $parkingAreaId = $cfg['parking_area_id'] ?? null;

            // defaults
            $totalCarSlots = 0;
            $occupiedCarSlots = 0;
            $availableMotorcycleCount = null;

            if ($parkingAreaId) {
                // try Eloquent models first, if they exist
                try {
                    // assume you have App\Models\CarSlot and App\Models\MotorcycleCount
                    if (class_exists(\App\Models\CarSlot::class)) {
                        $totalCarSlots = \App\Models\CarSlot::where('area_id', $parkingAreaId)->count();
                        $occupiedCarSlots = \App\Models\CarSlot::where('area_id', $parkingAreaId)->where('occupied', 1)->count();
                    } else {
                        // fallback to DB
                        $totalCarSlots = DB::table('car_slots')->where('area_id', $parkingAreaId)->count();
                        $occupiedCarSlots = DB::table('car_slots')->where('area_id', $parkingAreaId)->where('occupied', 1)->count();
                    }

                    if (class_exists(\App\Models\MotorcycleCount::class)) {
                        $mc = \App\Models\MotorcycleCount::where('area_id', $parkingAreaId)->first();
                        $availableMotorcycleCount = $mc?->available_count ?? null;
                    } else {
                        $mc = DB::table('motorcycle_counts')->where('area_id', $parkingAreaId)->first();
                        $availableMotorcycleCount = $mc->available_count ?? null;
                    }
                } catch (\Throwable $e) {
                    // If schema differs, keep defaults and log if desired
                    $totalCarSlots = 0;
                    $occupiedCarSlots = 0;
                    $availableMotorcycleCount = null;
                }
            }

            $availableCarSlots = max(0, $totalCarSlots - $occupiedCarSlots);

            // Determine state
            $state = 'unknown';
            if (!$enabled) {
                $state = 'disabled';
            } elseif ($totalCarSlots > 0 && $availableCarSlots > 0) {
                $state = 'available';
            } elseif ($totalCarSlots > 0 && $availableCarSlots === 0) {
                // cars full. check motorcycles
                if ($availableMotorcycleCount === null) {
                    // unknown motorcycle info
                    $state = 'full';
                } elseif ((int)$availableMotorcycleCount > 0) {
                    $state = 'moto_only';
                } else {
                    $state = 'full';
                }
            } else {
                // no car slots recorded for this parking area
                if ($availableMotorcycleCount !== null && (int)$availableMotorcycleCount > 0) {
                    $state = 'available';
                } elseif ($availableMotorcycleCount === 0) {
                    $state = 'full';
                } else {
                    $state = 'unknown';
                }
            }

            $statuses[$areaKey] = [
                'state' => $state,
                'total' => (int)$totalCarSlots,
                'occupied' => (int)$occupiedCarSlots,
                'available_cars' => (int)$availableCarSlots,
                'motorcycle_available' => $availableMotorcycleCount !== null ? (int)$availableMotorcycleCount : null,
            ];
        }

        $this->areaStatuses = $statuses;
    }

    public function render()
    {
        return view('livewire.admin.parking-map-live-view-component', [
            'map' => $this->map,
            'areaConfig' => $this->areaConfig,
            'areaStatuses' => $this->areaStatuses,
        ]);
    }
}
