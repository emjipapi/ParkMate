<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\ParkingSlot;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\RfidController;

Route::get('parking-slots', function () {
    return ParkingSlot::all();
});

Route::get('parking-slots/update', function (Request $request) {
    $slotId = $request->query('slot');
    $status = $request->query('status');

    if (!$slotId || !is_numeric($status)) {
        return response()->json(['error' => 'Invalid input'], 400);
    }

    $slot = ParkingSlot::find($slotId);

    if (!$slot) {
        return response()->json(['error' => 'Slot not found'], 404);
    }

    $slot->status = $status;
    $slot->save();

    return response()->json(['message' => 'Slot updated', 'slot' => $slot]);
});

Route::post('/rfid', [RfidController::class, 'logScan']);

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

