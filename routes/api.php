<?php

use App\Http\Controllers\DeviceController;
use App\Http\Controllers\RfidController;
use App\Models\CarSlot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\ParkingMap;

Route::get('car-slots', function () {
    return CarSlot::all();
});

Route::get('car-slots/update', function (Request $request) {
    $label = $request->query('slot');       // C1, C2, etc.
    $occupied = $request->query('occupied');

    if (! $label || ! is_numeric($occupied)) {
        return response()->json(['error' => 'Invalid input'], 400);
    }

    $slot = CarSlot::where('label', $label)->first();

    if (! $slot) {
        return response()->json(['error' => 'Slot not found'], 404);
    }

    $slot->occupied = $occupied;
    $slot->save();

    return response()->json(['message' => 'Slot updated', 'slot' => $slot]);
});

Route::get('area-status', function (Request $request) {
    $areaId = $request->query('area_id');

    $carAvailable = CarSlot::where('area_id', $areaId)
        ->where('occupied', 0)
        ->count();

    $motoAvailable = DB::table('motorcycle_counts')
        ->where('area_id', $areaId)
        ->value('available_count');

    return response()->json([
        'full' => ($carAvailable === 0 && $motoAvailable === 0),
    ]);
});

// main gate
Route::post('/rfid', [RfidController::class, 'logScan']);

Route::post('/rfid-area', [RfidController::class, 'logScanArea']);

Route::get('heartbeat', [DeviceController::class, 'heartbeat']);

Route::get('/parking-map/{map}/statuses', function (ParkingMap $map) {
    $areaConfig = (array) ($map->area_config ?? []);
    $areaStatuses = [];
    
    foreach ($areaConfig as $areaKey => $cfg) {
        $enabled = !empty($cfg['enabled']);
        $parkingAreaId = $cfg['parking_area_id'] ?? null;
        
        $totalCarSlots = 0;
        $occupiedCarSlots = 0;
        $availableMotorcycleCount = null;
        $motorcycleTotal = null; // ADD THIS LINE
        
        if ($parkingAreaId) {
            $totalCarSlots = \App\Models\CarSlot::where('area_id', $parkingAreaId)->count();
            $occupiedCarSlots = \App\Models\CarSlot::where('area_id', $parkingAreaId)->where('occupied', 1)->count();
            
            $mc = \App\Models\MotorcycleCount::where('area_id', $parkingAreaId)->first();
            $availableMotorcycleCount = $mc?->available_count ?? null;
            $motorcycleTotal = $mc?->total_available ?? null; // ADD THIS LINE
        }
        
        $availableCarSlots = max(0, $totalCarSlots - $occupiedCarSlots);
        
        // Determine state - exact same logic as Livewire component
        $state = 'unknown';
        if (!$enabled) {
            $state = 'disabled';
        } elseif ($totalCarSlots > 0 && $availableCarSlots > 0) {
            $state = 'available';
        } elseif ($totalCarSlots > 0 && $availableCarSlots === 0) {
            if ($availableMotorcycleCount === null) {
                $state = 'full';
            } elseif ((int)$availableMotorcycleCount > 0) {
                $state = 'moto_only';
            } else {
                $state = 'full';
            }
        } else {
            if ($availableMotorcycleCount !== null && (int)$availableMotorcycleCount > 0) {
                $state = 'available';
            } elseif ($availableMotorcycleCount === 0) {
                $state = 'full';
            } else {
                $state = 'unknown';
            }
        }
        
        $areaStatuses[$areaKey] = [
            'state' => $state,
            'total' => (int)$totalCarSlots,
            'occupied' => (int)$occupiedCarSlots,
            'available_cars' => (int)$availableCarSlots,
            'motorcycle_available' => $availableMotorcycleCount !== null ? (int)$availableMotorcycleCount : null,
            'motorcycle_total' => $motorcycleTotal !== null ? (int)$motorcycleTotal : null,
        ];
    }
    
    return response()->json([
        'areaStatuses' => $areaStatuses,
        'areaConfig' => $areaConfig // Also return this if you need config updates
    ]);
});