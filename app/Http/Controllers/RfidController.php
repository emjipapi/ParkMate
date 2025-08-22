<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ActivityLog;

class RfidController extends Controller
{
    public function logScan(Request $request)
    {
        // Validate input
        $request->validate([
            'epc' => 'required|string',
        ]);

        $epc = $request->input('epc');

        try {
            // Find user by RFID
            $user = User::where('rfid_tag', $epc)->first();
            if (!$user) {
                return response()->json([
                    'message' => 'User not found',
                ], 404);
            }

            // Prevent double logging (per actor)
            $lastScan = ActivityLog::where('actor_type', 'user')
                ->where('actor_id', $user->id)
                ->latest()
                ->first();

            if ($lastScan && $lastScan->created_at->diffInSeconds(now()) < 5) {
                return response()->json([
                    'message' => 'Scan too soon',
                    'last_scan_time' => $lastScan->created_at,
                ], 200);
            }

            // Toggle in/out status
            $newStatus = $user->in_out === 'IN' ? 'OUT' : 'IN';
            $user->in_out = $newStatus;
            $user->save();

            // Log the scan in activity_logs
            $log = ActivityLog::create([
                'actor_type' => 'user',
                'actor_id'   => $user->id,
                'action'     => $newStatus === 'IN' ? 'entry' : 'exit',
                'details'    => "User {$user->firstname} {$user->lastname} scanned RFID. Status: {$newStatus}",
                'created_at' => now(),
            ]);

            return response()->json([
                'message' => 'Scan logged successfully',
                'user'    => $user->only('id', 'firstname', 'lastname', 'in_out'),
                'log'     => $log,
            ], 201);

        } catch (\Exception $e) {
            // Return full error in JSON
            return response()->json([
                'message' => 'Failed to log scan',
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ], 500);
        }
    }
}
