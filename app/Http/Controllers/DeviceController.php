<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ParkingArea;
use App\Models\CarSlot;

class DeviceController extends Controller
{
    public function heartbeat(Request $request)
    {
        $areaId = $request->query('area_id');

        if (!$areaId) {
            return response()->json(['error' => 'Missing area_id'], 400);
        }

        // Update the car_slots last_seen for this area
        $updated = CarSlot::where('area_id', $areaId)->update(['last_seen' => now()]);

        if ($updated === 0) {
            return response()->json(['error' => 'No slots found for area'], 404);
        }

        return response()->json(['message' => 'Heartbeat recorded']);
    }

    public function checkOfflineDevices()
    {
        // Find car slots that haven't sent heartbeat in 15+ seconds
        $offlineSlots = CarSlot::where('last_seen', '<', now()->subSeconds(15))
                              ->orWhereNull('last_seen')
                              ->get();

        foreach ($offlineSlots as $slot) {
            if ($slot->occupied == 1) {
                $slot->update(['occupied' => 0]);
                \Log::info("Reset offline slot: {$slot->label} in area {$slot->area_id}");
            }
        }

        return response()->json([
            'message' => 'Offline check completed',
            'reset_slots' => $offlineSlots->pluck('label')
        ]);
    }
}