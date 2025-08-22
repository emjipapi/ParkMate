<?php
use Illuminate\Support\Facades\DB;

if (!function_exists('activity_log')) {
    function activity_log($actorType, $actorId, $action, $details = null)
    {
       DB::table('activity_logs')->insert([
            'actor_type' => $actorType, // string: 'admin' or 'user'
            'actor_id'   => $actorId,   // integer ID from the guard
            'action'     => $action,
            'details'    => $details,
            'created_at' => now(),
        ]);
    }
}

