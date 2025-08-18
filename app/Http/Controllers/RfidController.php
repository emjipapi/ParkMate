<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ActivityLog;

class RfidController extends Controller
{
    public function logScan(Request $request)
    {
        $request->validate([
            'epc' => 'required|string',
        ]);

        $epc = $request->input('epc');

        // Find user by RFID tag
        $user = User::where('rfid_tag', $epc)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Prevent double logging in short time
        $lastScan = ActivityLog::where('rfid_tag', $epc)->latest()->first();
        if ($lastScan && $lastScan->created_at->diffInSeconds(now()) < 5) {
            return response()->json(['message' => 'Scan too soon'], 200);
        }

        // Toggle in/out
        $newStatus = $user->in_out === 'IN' ? 'OUT' : 'IN';
        $user->in_out = $newStatus;
        $user->save();

        // Log activity
        $log = ActivityLog::create([
            'user_id' => $user->id,
            'rfid_tag' => $epc,
            'status' => $newStatus,
        ]);

        return response()->json(['message' => 'Logged', 'log' => $log], 201);
    }
}
