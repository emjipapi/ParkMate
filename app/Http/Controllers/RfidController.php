<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ActivityLog;
use App\Models\ParkingArea;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RfidController extends Controller
{
    /**
     * Scan at main gate
     */
    public function logScan(Request $request)
    {
        $request->validate(['epc' => 'required|string']);
        $epc = $request->input('epc');

        // Get vehicle first, then user
        $vehicle = DB::table('vehicles')->where('rfid_tag', $epc)->first();
        if (!$vehicle) {
            return response()->json(['message' => 'Vehicle not found'], 404);
        }
        
        $user = User::find($vehicle->user_id);
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

        $pendingViolations = DB::table('violations')
    ->where('violator_id', $user->id)  // <-- links to the student/employee
    ->where('status', 'Approved')
    ->whereNull('action_taken')       // still unresolved
    ->count();

$violationThreshold = 3;

if ($pendingViolations >= $violationThreshold) {
    // Deny entry and log it
    ActivityLog::create([
        'actor_type' => 'system',
        'actor_id' => $user->id,
        'area_id' => null,
        'action' => 'denied_entry',
        'details' => "User {$user->firstname} {$user->lastname} denied entry due to {$pendingViolations} unresolved approved violations.",
        'created_at' => now(),
    ]);

    return response()->json([
        'message' => "Access denied: {$pendingViolations} unresolved violations",
    ], 403);
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

            // Create exit logs for all active areas and handle motorcycle counts
            foreach ($activeAreaIds as $areaId) {
                $area = ParkingArea::find($areaId);
                $areaName = $area ? $area->name : 'Unknown area';

                // Handle motorcycle count restoration when auto-exiting (use the vehicle we already found)
                if ($vehicle->type === 'motorcycle') {
                    $moto = DB::table('motorcycle_counts')
                        ->where('area_id', $areaId)
                        ->first();

                    if ($moto && $moto->available_count < $moto->total_available) {
                        DB::table('motorcycle_counts')
                            ->where('id', $moto->id)
                            ->increment('available_count');
                        
                        Log::info("Motorcycle auto-exited from area", [
                            'user_id' => $user->id,
                            'vehicle_id' => $vehicle->id,
                            'vehicle_type' => $vehicle->type,
                            'area_id' => $areaId,
                            'before' => $moto->available_count,
                            'after' => $moto->available_count + 1,
                        ]);
                    }
                }

                ActivityLog::create([
                    'actor_type' => 'user',
                    'actor_id' => $user->id,
                    'action' => 'exit',
                    'details' => "User {$user->firstname} {$user->lastname} automatically exited area {$areaName}",
                    'area_id' => $areaId,
                    'created_at' => $now,
                ]);
            }
        }

        // 4. Log main gate scan
        $log = ActivityLog::create([
            'actor_type' => 'user',
            'actor_id' => $user->id,
            'action' => $newAction,
            'details' => "User {$user->firstname} {$user->lastname} scanned RFID at main gate. Action: {$newAction}",
            'created_at' => $now,
        ]);

        return response()->json([
            'message' => 'Scan logged successfully',
            'user' => $user->only('id', 'firstname', 'lastname'),
            'vehicle_type' => $vehicle->type,
            'log' => $log,
        ], 201);
    }

    /**
     * Scan inside a parking area
     */
    public function logScanArea(Request $request)
    {
        $request->validate([
            'epc' => 'required|string',
            'area_id' => 'required|integer',
        ]);

        $epc = $request->input('epc');
        $areaId = $request->input('area_id');

        try {
            // Get vehicle first, then user
            $vehicle = DB::table('vehicles')->where('rfid_tag', $epc)->first();
            if (!$vehicle) {
                return response()->json(['message' => 'Vehicle not found'], 404);
            }
            
            $user = User::find($vehicle->user_id);
            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

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

            Log::info("Vehicle scan at area", [
                'user_id' => $user->id,
                'vehicle_id' => $vehicle->id,
                'vehicle_type' => $vehicle->type,
                'area_id' => $areaId,
                'action' => $newAction,
            ]);

            // Handle motorcycle count updates ONLY for motorcycle vehicles
            if ($vehicle->type === 'motorcycle') {
                $moto = DB::table('motorcycle_counts')
                    ->where('area_id', $areaId)
                    ->first();

                if ($moto) {
                    Log::info("Current motorcycle count", [
                        'area_id' => $areaId,
                        'available_count' => $moto->available_count,
                        'total_available' => $moto->total_available,
                        'action' => $newAction,
                    ]);

                    if ($newAction === 'entry' && $moto->available_count > 0) {
                        // Motorcycle entering - decrease available count
                        DB::table('motorcycle_counts')
                            ->where('id', $moto->id)
                            ->decrement('available_count');
                        
                        Log::info("Motorcycle entered area", [
                            'user_id' => $user->id,
                            'vehicle_id' => $vehicle->id,
                            'area_id' => $areaId,
                            'before' => $moto->available_count,
                            'after' => $moto->available_count - 1,
                        ]);
                    } elseif ($newAction === 'exit' && $moto->available_count < $moto->total_available) {
                        // Motorcycle exiting - increase available count
                        DB::table('motorcycle_counts')
                            ->where('id', $moto->id)
                            ->increment('available_count');
                        
                        Log::info("Motorcycle exited area", [
                            'user_id' => $user->id,
                            'vehicle_id' => $vehicle->id,
                            'area_id' => $areaId,
                            'before' => $moto->available_count,
                            'after' => $moto->available_count + 1,
                        ]);
                    } elseif ($newAction === 'entry' && $moto->available_count <= 0) {
                        Log::warning("Motorcycle tried to enter but no slots available", [
                            'user_id' => $user->id,
                            'vehicle_id' => $vehicle->id,
                            'area_id' => $areaId,
                            'available_count' => $moto->available_count,
                        ]);
                        
                        return response()->json([
                            'message' => 'No motorcycle slots available in this area',
                            'available_count' => $moto->available_count,
                        ], 400);
                    }
                } else {
                    Log::warning("No motorcycle_counts row found for area", [
                        'area_id' => $areaId
                    ]);
                }
            } else {
                Log::info("Vehicle is not a motorcycle", [
                    'user_id' => $user->id,
                    'vehicle_id' => $vehicle->id,
                    'vehicle_type' => $vehicle->type,
                ]);
            }

            $log = ActivityLog::create([
                'actor_type' => 'user',
                'actor_id' => $user->id,
                'action' => $newAction,
                'details' => "User {$user->firstname} {$user->lastname} scanned RFID in area {$areaName}. Action: {$newAction}",
                'area_id' => $areaId,
                'created_at' => now(),
            ]);

            return response()->json([
                'message' => 'Scan logged successfully',
                'user' => $user->only('id', 'firstname', 'lastname'),
                'vehicle_type' => $vehicle->type,
                'log' => $log,
                'action' => $newAction,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error in logScanArea', [
                'epc' => $epc,
                'area_id' => $areaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'message' => 'Failed to log scan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}