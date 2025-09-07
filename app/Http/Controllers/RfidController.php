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
     * Check if user has 3+ approved violations and should be denied entry
     */
    private function checkViolationStatus($userId)
    {
        try {
            // Check if user has 3+ approved violations
            $approvedViolationCount = DB::table('violations')
                ->where('violator_id', $userId)  // violator_id matches user_id from vehicles
                ->where('status', 'approved')     // status must be 'approved'
                ->count();

            return $approvedViolationCount >= 3;
            
        } catch (\Exception $e) {
            // Log the error but don't block the scan if violation check fails
            Log::error('Error checking violation status', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            // Return false to allow entry if violation check fails
            return false;
        }
    }

    /**
     * Scan at main gate
     */
    public function logScan(Request $request)
    {
        try {
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
            $lastMainGateScan = ActivityLog::where('actor_type', 'user')
                ->where('actor_id', $user->id)
                ->whereNull('area_id')
                ->latest()
                ->first();

            $newAction = 'entry';
            if ($lastMainGateScan && $lastMainGateScan->action === 'entry') {
                $newAction = 'exit';
            }

            // 3. CHECK FOR VIOLATIONS - Only deny ENTRY, not EXIT
            if ($newAction === 'entry' && $this->checkViolationStatus($user->id)) {
                Log::warning("Entry denied due to violations", [
                    'user_id' => $user->id,
                    'epc' => $epc,
                    'reason' => '3 or more approved violations'
                ]);

                // Create ActivityLog entry for denied access
                ActivityLog::create([
                    'actor_type' => 'system',
                    'actor_id' => 1,
                    'action' => 'denied_entry',
                    'details' => "User {$user->firstname} {$user->lastname} denied entry due to 3 or more approved violations",
                    'area_id' => null,
                    'created_at' => $now,
                ]);

                return response()->json([
                    'message' => 'Entry denied - 3 or more approved violations',
                    'user' => $user->only(['id', 'firstname', 'lastname']),
                    'vehicle_type' => $vehicle->type,
                    'denied' => true,
                ], 403);
            }

            // 4. If exiting main gate, automatically exit all currently active areas
            if ($newAction === 'exit') {
                $activeAreaIds = [];
                $allUserAreas = ActivityLog::where('actor_type', 'user')
                    ->where('actor_id', $user->id)
                    ->whereNotNull('area_id')
                    ->distinct()
                    ->pluck('area_id');

                foreach ($allUserAreas as $areaId) {
                    $lastAreaScan = ActivityLog::where('actor_type', 'user')
                        ->where('actor_id', $user->id)
                        ->where('area_id', $areaId)
                        ->orderBy('created_at', 'desc')
                        ->first();

                    if ($lastAreaScan && $lastAreaScan->action === 'entry') {
                        $activeAreaIds[] = $areaId;
                    }
                }

                foreach ($activeAreaIds as $areaId) {
                    $area = ParkingArea::find($areaId);
                    $areaName = $area ? $area->name : 'Unknown area';

                    if ($vehicle->type === 'motorcycle') {
                        $moto = DB::table('motorcycle_counts')
                            ->where('area_id', $areaId)
                            ->first();

                        if ($moto && $moto->available_count < $moto->total_available) {
                            DB::table('motorcycle_counts')
                                ->where('id', $moto->id)
                                ->increment('available_count');
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

            // 5. Log main gate scan
            $log = ActivityLog::create([
                'actor_type' => 'user',
                'actor_id' => $user->id,
                'action' => $newAction,
                'details' => "User {$user->firstname} {$user->lastname} scanned RFID at main gate. Action: {$newAction}",
                'created_at' => $now,
            ]);

            return response()->json([
                'message' => 'Scan logged successfully',
                'user' => $user->only(['id', 'firstname', 'lastname']),
                'vehicle_type' => $vehicle->type,
                'log' => $log,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error in logScan', [
                'epc' => $request->input('epc'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'message' => 'Failed to log scan',
                'error' => $e->getMessage(),
            ], 500);
        }
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
            $now = now();

            $lastScan = ActivityLog::where('actor_type', 'user')
                ->where('actor_id', $user->id)
                ->where('area_id', $areaId)
                ->latest()
                ->first();

            if ($lastScan && $lastScan->created_at->diffInSeconds($now) < 5) {
                return response()->json([
                    'message' => 'Scan too soon',
                    'last_scan_time' => $lastScan->created_at,
                ], 200);
            }

            $newAction = ($lastScan && $lastScan->action === 'entry') ? 'exit' : 'entry';

            // CHECK FOR VIOLATIONS - Only deny ENTRY to parking areas, not EXIT
            if ($newAction === 'entry' && $this->checkViolationStatus($user->id)) {
                Log::warning("Parking area entry denied due to violations", [
                    'user_id' => $user->id,
                    'area_id' => $areaId,
                    'epc' => $epc,
                    'reason' => '3 or more approved violations'
                ]);

                // Create ActivityLog entry for denied access to parking area
                ActivityLog::create([
                    'actor_type' => 'system',
                    'actor_id' => 1,
                    'action' => 'denied_entry',
                    'details' => "User {$user->firstname} {$user->lastname} denied entry to {$areaName} due to 3 or more approved violations",
                    'area_id' => $areaId,
                    'created_at' => $now,
                ]);

                return response()->json([
                    'message' => "Entry denied to {$areaName} - 3 or more approved violations",
                    'user' => $user->only(['id', 'firstname', 'lastname']),
                    'vehicle_type' => $vehicle->type,
                    'area_name' => $areaName,
                    'denied' => true,
                ], 403);
            }

            if ($vehicle->type === 'motorcycle') {
                $moto = DB::table('motorcycle_counts')
                    ->where('area_id', $areaId)
                    ->first();

                if ($moto) {
                    if ($newAction === 'entry' && $moto->available_count > 0) {
                        DB::table('motorcycle_counts')
                            ->where('id', $moto->id)
                            ->decrement('available_count');
                    } elseif ($newAction === 'exit' && $moto->available_count < $moto->total_available) {
                        DB::table('motorcycle_counts')
                            ->where('id', $moto->id)
                            ->increment('available_count');
                    } elseif ($newAction === 'entry' && $moto->available_count <= 0) {
                        return response()->json([
                            'message' => 'No motorcycle slots available in this area',
                            'available_count' => $moto->available_count,
                        ], 400);
                    }
                }
            }

            $log = ActivityLog::create([
                'actor_type' => 'user',
                'actor_id' => $user->id,
                'action' => $newAction,
                'details' => "User {$user->firstname} {$user->lastname} scanned RFID in area {$areaName}. Action: {$newAction}",
                'area_id' => $areaId,
                'created_at' => $now,
            ]);

            return response()->json([
                'message' => 'Scan logged successfully',
                'user' => $user->only(['id', 'firstname', 'lastname']),
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