<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ActivityLog;
use App\Models\ParkingArea;

class RfidController extends Controller
{
    /**
     * Scan at main gate
     */
public function logScan(Request $request)
{
    $request->validate(['epc' => 'required|string']);
    $epc = $request->input('epc');

    $user = User::where('rfid_tag', $epc)->first();
    if (!$user) {
        return response()->json(['message' => 'User not found'], 404);
    }

    $now = now();

    // 1. Global cooldown: last scan of this user (anywhere)
    $lastScan = ActivityLog::where('actor_type', 'user')
        ->where('actor_id', $user->id)
        ->latest()
        ->first();

    if ($lastScan && $lastScan->created_at->diffInSeconds($now) < 5) {
        return response()->json([
            'message' => 'Scan too soon',
            'last_scan_time' => $lastScan->created_at,
        ], 200);
    }

    // 2. Determine main gate action based on overall building status
    // Find the last main gate scan (area_id is null)
    $lastMainGateScan = ActivityLog::where('actor_type', 'user')
        ->where('actor_id', $user->id)
        ->whereNull('area_id')
        ->latest()
        ->first();

    // If last main gate action was entry, then this scan should be exit
    $newAction = 'entry';
    if ($lastMainGateScan && $lastMainGateScan->action === 'entry') {
        $newAction = 'exit';
    }

    // 3. If exiting main gate, automatically exit all currently active areas
    if ($newAction === 'exit') {
        // Find all areas where user has entries but no corresponding exits
        $activeAreaIds = [];
        
        // Get all unique area IDs where user has logged activities
        $allUserAreas = ActivityLog::where('actor_type', 'user')
            ->where('actor_id', $user->id)
            ->whereNotNull('area_id')
            ->distinct()
            ->pluck('area_id');

        // For each area, check if user is currently inside (last action was entry)
        foreach ($allUserAreas as $areaId) {
            $lastAreaScan = ActivityLog::where('actor_type', 'user')
                ->where('actor_id', $user->id)
                ->where('area_id', $areaId)
                ->orderBy('created_at', 'desc')
                ->first();

            // If last action in this area was entry, user is still inside
            if ($lastAreaScan && $lastAreaScan->action === 'entry') {
                $activeAreaIds[] = $areaId;
            }
        }

        // Create exit logs for all active areas
        foreach ($activeAreaIds as $areaId) {
            $area = ParkingArea::find($areaId);
            $areaName = $area ? $area->name : 'Unknown area';
            
            ActivityLog::create([
                'actor_type' => 'user',
                'actor_id'   => $user->id,
                'action'     => 'exit',
                'details'    => "User {$user->firstname} {$user->lastname} automatically exited area {$areaName}",
                'area_id'    => $areaId,
                'created_at' => $now,
            ]);
        }
    }

    // 4. Log main gate scan
    $log = ActivityLog::create([
        'actor_type' => 'user',
        'actor_id'   => $user->id,
        'action'     => $newAction,
        'details'    => "User {$user->firstname} {$user->lastname} scanned RFID at main gate. Action: {$newAction}",
        'created_at' => $now,
    ]);

    return response()->json([
        'message' => 'Scan logged successfully',
        'user'    => $user->only('id','firstname','lastname'),
        'log'     => $log,
    ], 201);
}


    /**
     * Scan inside a parking area
     */
    public function logScanArea(Request $request)
    {
        $request->validate([
            'epc'     => 'required|string',
            'area_id' => 'required|integer',
        ]);

        $epc = $request->input('epc');
        $areaId = $request->input('area_id');

        try {
            $user = User::where('rfid_tag', $epc)->firstOrFail();
            $area = ParkingArea::find($areaId);
            $areaName = $area ? $area->name : 'Unknown area';

            // Last scan for cooldown and determining action
            $lastScan = ActivityLog::where('actor_type', 'user')
                ->where('actor_id', $user->id)
                ->where('area_id', $areaId)
                ->latest()
                ->first();

            if ($lastScan && $lastScan->created_at->diffInSeconds(now()) < 5) {
                return response()->json([
                    'message' => 'Scan too soon',
                    'last_scan_time' => $lastScan->created_at,
                ], 200);
            }

            // Determine action based on last scan in this area
            $newAction = ($lastScan && $lastScan->action === 'entry') ? 'exit' : 'entry';

            $log = ActivityLog::create([
                'actor_type' => 'user',
                'actor_id'   => $user->id,
                'action'     => $newAction,
                'details'    => "User {$user->firstname} {$user->lastname} scanned RFID in area {$areaName}. Action: {$newAction}",
                'area_id'    => $areaId,
                'created_at' => now(),
            ]);

            return response()->json([
                'message' => 'Scan logged successfully',
                'user'    => $user->only('id','firstname','lastname'),
                'log'     => $log,
            ], 201);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'User not found'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to log scan',
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ], 500);
        }
    }
}