<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use Carbon\Carbon;

class LiveAttendanceController extends Controller
{
    // show blade
    public function index()
    {
        return view('admin.live-attendance-mode');
    }

    // return latest scans as JSON
public function latestScans(Request $request)
{
    $since = $request->query('since', null);

    $query = ActivityLog::with(['user' => function($q) {
        // select only fields we need to reduce payload
        $q->select('id', 'firstname', 'lastname', 'profile_picture');
    }])
    ->where(function($q) {
        $q->where('actor_type', 'user')
          ->whereIn('action', ['entry', 'exit'])
          ->orWhere(function($sub) {
              $sub->where('actor_type','system')->where('action','denied_entry');
          });
    });

    if ($since) {
        // parse safely — assume ISO timestamp
        try {
            $dt = Carbon::parse($since);
            $query->where('created_at', '>', $dt);
        } catch (\Exception $e) {
            // ignore invalid since param
        }
    }

    // since client expects "newer-first", order by created_at desc
    $logs = $query->orderBy('created_at', 'desc')
                  ->take(10) // more than 3 so client can pick up multiple new ones if many arrived
                  ->get()
                  ->map(function($log) {
                      $user = $log->user;
                      $status = ($log->action === 'denied_entry') ? 'DENIED' : ($log->action === 'entry' ? 'IN' : 'OUT');

                      return [
                          'id' => $log->id,
                          'created_at' => $log->created_at->toIso8601String(),
                          'name' => "{$user->lastname}, {$user->firstname}",
                          'status' => $status,
                          'picture' => $user->profile_picture ? route('profile.picture', ['filename' => $user->profile_picture]) : asset('images/placeholder.jpg'),
                      ];
                  });

    return response()->json($logs);
}

    // process EPCs that were pushed into cache (POST endpoint triggered by frontend)
    public function pollEpc(Request $request)
    {
        // NOTE: protect this route with auth / admin middleware in routes
        $scannedTags = Cache::pull('epc_list', []);
        $now = now();
        $processed = 0;

        foreach ($scannedTags as $epc) {
            $user = User::where('rfid_tag', $epc)->first();
            if (!$user) continue;

            $lastScan = ActivityLog::where('rfid_tag', $epc)->latest()->first();

            // this uses created_at diff; if you use created_at or timestamps differently adjust here
            if ($lastScan && $lastScan->created_at->diffInSeconds($now) < 5) {
                continue;
            }

            $newStatus = ($lastScan && ($lastScan->status === 'IN')) ? 'OUT' : 'IN';

            ActivityLog::create([
                'user_id' => $user->id,
                'rfid_tag' => $epc,
                'status' => $newStatus,
                'details' => "User {$user->firstname} {$user->lastname} scanned {$newStatus}",
                // you may want to set actor_type, action, etc. depending on your schema
            ]);

            $processed++;
        }

        // return summary
        return response()->json([
            'processed' => $processed,
            'pulled' => count($scannedTags),
        ]);
    }
}
