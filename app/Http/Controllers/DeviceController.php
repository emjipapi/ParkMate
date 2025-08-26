<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeviceController extends Controller
{
    public function heartbeat(Request $request)
{
    $areaId = $request->query('area_id');

    if (!$areaId) {
        return response()->json(['error' => 'Missing area_id'], 400);
    }

    DB::table('areas')
        ->where('id', $areaId)
        ->update(['last_seen' => now()]);

    return response()->json(['message' => 'Heartbeat recorded']);
}
}
