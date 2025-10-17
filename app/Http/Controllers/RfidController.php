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
    // Cooldown in seconds for valid scans
    public $scanCooldown = 5;
    
    // Cooldown in seconds for unknown RFID tags
    public $unknownTagCooldown = 30;

    /**
     * Check if user has 3+ approved violations and should be denied entry
     */
private function checkViolationStatus($userId): bool
{
    try {
        $count = DB::table('violations')
            ->where('violator_id', $userId)
            ->whereIn('status', ['approved', 'for_endorsement'])
            ->count();

        return $count >= 3;
    } catch (\Exception $e) {
        Log::error('Error checking violation status', [
            'user_id' => $userId,
            'error' => $e->getMessage()
        ]);

        return false;
    }
}


    /**
     * Check and log unknown RFID tag with cooldown
     */
    private function logUnknownTag($epc, $areaId = null)
    {
        $now = now();
        
        // Check cooldown for this specific tag
        $lastUnknownScan = DB::table('unknown_rfid_logs')
            ->where('rfid_tag', $epc)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastUnknownScan) {
            $lastScanTime = \Carbon\Carbon::parse($lastUnknownScan->created_at);
            $secondsSinceLastScan = $lastScanTime->diffInSeconds($now);
            
            // If not enough time has passed, deny logging
            if ($secondsSinceLastScan < $this->unknownTagCooldown) {
                return [
                    'logged' => false,
                    'message' => 'Unknown tag scan too soon',
                    'cooldown_remaining' => $this->unknownTagCooldown - $secondsSinceLastScan
                ];
            }
        }

        // Log the unknown tag
        DB::table('unknown_rfid_logs')->insert([
            'rfid_tag' => $epc,
            'area_id' => $areaId,
            'created_at' => $now,
        ]);

        return [
            'logged' => true,
            'message' => 'Unknown tag logged'
        ];
    }

    /**
     * Scan at main gate
     */
    public function logScan(Request $request)
    {
        try {
            $request->validate(['epc' => 'required|string']);
            $epc = $request->input('epc');
            $areaId = $request->input('area_id');

            // Get vehicle first, then user
            $vehicle = DB::table('vehicles')->where('rfid_tag', $epc)->first();
            
            if (!$vehicle) {
                $unknownResult = $this->logUnknownTag($epc, $areaId);
                
                return response()->json([
                    'message' => 'Vehicle not found',
                    'epc' => $epc,
                    'logged_as_unknown' => $unknownResult['logged'],
                    'info' => $unknownResult['message']
                ], 404);
            }
            
            $user = User::find($vehicle->user_id);
            if (!$user) {
                $unknownResult = $this->logUnknownTag($epc, $areaId);
                
                return response()->json([
                    'message' => 'User not found',
                    'logged_as_unknown' => $unknownResult['logged'],
                    'info' => $unknownResult['message']
                ], 404);
            }

            $now = now();

            // 1. Global cooldown: last scan of this user (anywhere)
            $lastScan = ActivityLog::where('actor_type', 'user')
                ->where('actor_id', $user->id)
                ->latest()
                ->first();

            if ($lastScan && $lastScan->created_at->diffInSeconds($now) < $this->scanCooldown) {
                return response()->json([
                    'message' => 'Scan too soon',
                    'last_scan_time' => $lastScan->created_at,
                ], 200);
            }

            // 2. Determine main gate action based on overall building status
            $lastMainGateScan = ActivityLog::where('actor_type', 'user')
                ->where('actor_id', $user->id)
                ->whereNull('area_id')
                ->whereIn('action', ['entry', 'exit'])
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

                ActivityLog::create([
                    'actor_type' => 'system',
                    'actor_id' => 1,
                    'action' => 'denied_entry',
                    'details' => "User {$user->firstname} {$user->lastname} denied entry due to 3 or more approved violations | {$epc} - {$vehicle->type}",
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
                        'details' => "User {$user->firstname} {$user->lastname} automatically exited area {$areaName} | {$epc} - {$vehicle->type}",
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
                'details' => "User {$user->firstname} {$user->lastname} scanned RFID at main gate. Action: {$newAction} | {$epc} - {$vehicle->type}",
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
            $unknownResult = $this->logUnknownTag($epc, $areaId);
            
            return response()->json([
                'message' => 'Vehicle not found',
                'epc' => $epc,
                'logged_as_unknown' => $unknownResult['logged'],
                'info' => $unknownResult['message']
            ], 404);
        }

        $user = User::find($vehicle->user_id);
        
        if (!$user) {
            $unknownResult = $this->logUnknownTag($epc, $areaId);
            
            return response()->json([
                'message' => 'User not found',
                'logged_as_unknown' => $unknownResult['logged'],
                'info' => $unknownResult['message']
            ], 404);
        }

        $area = ParkingArea::find($areaId);
if (!$area) {
    return response()->json(['message' => 'Area not found'], 404);
}

$areaName = $area->name;
$now = now();

// CHECK COOLDOWN FIRST - before any other checks
$lastGlobal = ActivityLog::where('actor_type', 'user')
    ->where('actor_id', $user->id)
    ->latest()
    ->first();

if ($lastGlobal && $lastGlobal->created_at->diffInSeconds($now) < $this->scanCooldown) {
    return response()->json([
        'message' => 'Scan too soon',
        'last_scan_time' => $lastGlobal->created_at,
    ], 200);
}

// NOW check permission after cooldown passes
$userType = $this->getUserType($user);
$denialReason = $this->checkAreaPermission($area, $userType);

if ($denialReason) {
    // This denied_entry will now respect the cooldown
    ActivityLog::create([
        'actor_type' => 'system',
        'actor_id' => $user->id,
        'action' => 'denied_entry',
        'details' => "User {$user->firstname} {$user->lastname} denied entry to {$area->name} (user type not allowed: {$denialReason}) | {$epc} - {$vehicle->type}",
        'area_id' => $areaId,
        'created_at' => $now,
    ]);

    return response()->json([
        'message' => "Entry denied - {$denialReason}",
        'user' => $user->only(['id', 'firstname', 'lastname']),
        'vehicle_type' => $vehicle->type,
        'denied' => true,
    ], 403);
}
        // Check status at main gate
        $lastMainGateScan = ActivityLog::where('actor_type', 'user')
            ->where('actor_id', $user->id)
            ->whereNull('area_id')
            ->whereIn('action', ['entry', 'exit'])
            ->orderBy('created_at', 'desc')
            ->first();

        $needsMainGateEntry = !$lastMainGateScan || $lastMainGateScan->action === 'exit';

        // If we would need to create a main-gate entry, check violations first
        if ($needsMainGateEntry && $this->checkViolationStatus($user->id)) {
            Log::warning("Parking area entry denied due to violations (auto main gate check)", [
                'user_id' => $user->id,
                'area_id' => $areaId,
                'epc' => $epc,
                'reason' => '3 or more approved violations'
            ]);

            ActivityLog::create([
                'actor_type' => 'system',
                'actor_id' => 1,
                'action' => 'denied_entry',
                'details' => "User {$user->firstname} {$user->lastname} denied entry (main gate check) due to 3 or more approved violations | {$epc} - {$vehicle->type}",
                'area_id' => null,
                'created_at' => $now,
            ]);

            return response()->json([
                'message' => "Entry denied - 3 or more approved violations",
                'user' => $user->only(['id', 'firstname', 'lastname']),
                'vehicle_type' => $vehicle->type,
                'denied' => true,
            ], 403);
        }

        // Get last action for this area
        $lastAreaScan = ActivityLog::where('actor_type', 'user')
            ->where('actor_id', $user->id)
            ->where('area_id', $areaId)
            ->whereIn('action', ['entry', 'exit'])
            ->orderBy('created_at', 'desc')
            ->first();

        $newAction = ($lastAreaScan && $lastAreaScan->action === 'entry') ? 'exit' : 'entry';

        // Find other active areas
        $distinctAreaIds = ActivityLog::where('actor_type', 'user')
            ->where('actor_id', $user->id)
            ->whereNotNull('area_id')
            ->distinct()
            ->pluck('area_id');

        $activeAreaIdsToExit = [];
        foreach ($distinctAreaIds as $aId) {
            if ($aId == $areaId) continue;
            $last = ActivityLog::where('actor_type', 'user')
                ->where('actor_id', $user->id)
                ->where('area_id', $aId)
                ->whereIn('action', ['entry', 'exit'])
                ->orderBy('created_at', 'desc')
                ->first();

            if ($last && $last->action === 'entry') {
                $activeAreaIdsToExit[] = $aId;
            }
        }

        // Transaction for all changes
        DB::beginTransaction();
        try {
            // 1) Auto-create main gate entry if needed
            if ($needsMainGateEntry) {
                ActivityLog::create([
                    'actor_type' => 'user',
                    'actor_id' => $user->id,
                    'action' => 'entry',
                    'details' => "User {$user->firstname} {$user->lastname} scanned RFID at main gate (auto-created before area scan).",
                    'area_id' => null,
                    'created_at' => $now,
                ]);
            }

            // 2) Exit other active areas
            foreach ($activeAreaIdsToExit as $exitAreaId) {
                $exitArea = ParkingArea::find($exitAreaId);
                $exitAreaName = $exitArea ? $exitArea->name : 'Unknown area';

                if ($vehicle->type === 'motorcycle') {
                    $moto = DB::table('motorcycle_counts')
                        ->where('area_id', $exitAreaId)
                        ->lockForUpdate()
                        ->first();

                    if ($moto) {
                        $available = (int) $moto->available_count;
                        $total = (int) $moto->total_available;
                        if ($available < $total) {
                            DB::table('motorcycle_counts')
                                ->where('id', $moto->id)
                                ->increment('available_count');
                        }
                    }
                }

                ActivityLog::create([
                    'actor_type' => 'user',
                    'actor_id' => $user->id,
                    'action' => 'exit',
                    'details' => "User {$user->firstname} {$user->lastname} automatically exited area {$exitAreaName} (moved to another area). | {$epc} - {$vehicle->type}",
                    'area_id' => $exitAreaId,
                    'created_at' => $now,
                ]);
            }

            // 3) Handle motorcycle counts for target area
            if ($vehicle->type === 'motorcycle') {
                $moto = DB::table('motorcycle_counts')
                    ->where('area_id', $areaId)
                    ->lockForUpdate()
                    ->first();

                if (!$moto) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Parking area motorcycle slot configuration missing',
                    ], 500);
                }

                $available = (int) $moto->available_count;
                $total = (int) $moto->total_available;

                if ($newAction === 'entry') {
                    if ($available <= 0) {
                        DB::rollBack();
                        return response()->json([
                            'message' => 'No motorcycle slots available in this area',
                            'available_count' => $available,
                        ], 400);
                    }

                    DB::table('motorcycle_counts')
                        ->where('id', $moto->id)
                        ->decrement('available_count');
                } else {
                    if ($available < $total) {
                        DB::table('motorcycle_counts')
                            ->where('id', $moto->id)
                            ->increment('available_count');
                    }
                }
            }

            // 4) Create activity log for this area
            $log = ActivityLog::create([
                'actor_type' => 'user',
                'actor_id' => $user->id,
                'action' => $newAction,
                'details' => "User {$user->firstname} {$user->lastname} scanned RFID in area {$areaName}. Action: {$newAction} | {$epc} - {$vehicle->type}",
                'area_id' => $areaId,
                'created_at' => $now,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

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

/**
 * Determine user type based on student_id, employee_id, or guest status
 */
private function getUserType(User $user)
{
    if ($user->student_id) {
        return 'student';
    } elseif ($user->employee_id) {
        return 'employee';
    } else {
        return 'guest';
    }
}

/**
 * Check if a user type is allowed in the parking area
 * Returns denial reason if not allowed, null if allowed
 */
private function checkAreaPermission(ParkingArea $area, string $userType)
{
    $allowed = false;
    $allowedTypes = [];

    if ($area->allow_students && $userType === 'student') {
        $allowed = true;
    }
    if ($area->allow_employees && $userType === 'employee') {
        $allowed = true;
    }
    if ($area->allow_guests && $userType === 'guest') {
        $allowed = true;
    }

    if (!$allowed) {
        // Build list of allowed types
        if ($area->allow_students) $allowedTypes[] = 'Students';
        if ($area->allow_employees) $allowedTypes[] = 'Employees';
        if ($area->allow_guests) $allowedTypes[] = 'Guests';

        $allowedText = implode(', ', $allowedTypes) ?: 'No users allowed';
        return "Only {$allowedText} are allowed in this area";
    }

    return null;
}
}