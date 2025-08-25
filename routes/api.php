<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\ParkingSlot;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\RfidController;
use App\Models\CarSlot;

Route::get('car-slots', function () {
    return CarSlot::all();
});

Route::get('car-slots/update', function (Request $request) {
    $label = $request->query('slot');       // C1, C2, etc.
    $occupied = $request->query('occupied');

    if (!$label || !is_numeric($occupied)) {
        return response()->json(['error' => 'Invalid input'], 400);
    }

    $slot = CarSlot::where('label', $label)->first();

    if (!$slot) {
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
        ->value('available');

    return response()->json([
        'full' => ($carAvailable === 0 && $motoAvailable === 0)
    ]);
});

//main gate
Route::post('/rfid', [RfidController::class, 'logScan']);
Route::post('/rfid-area', [RfidController::class, 'logScanArea']);

//other areas
// Route::post('/rfid-area', [RfidController::class, 'logAreaScan']);

// Route::post('/rfid', function (Request $request) {
//     $epc = $request->input('epc');

//     // Fetch existing EPCs, or empty array
//     $epcList = Cache::get('epc_list', []);

//     // Append new EPC to the list (even if duplicate for now)
//     $epcList[] = $epc;

//     // Optional: Keep only the last 50 entries
//     $epcList = array_slice($epcList, -50);

//     // Save updated list back to cache
//     Cache::put('epc_list', $epcList, 60); // 60 seconds expiration

//     return response()->json(['status' => 'ok']);
// });

