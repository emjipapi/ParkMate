<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminMiddleware
{
    public function handle($request, Closure $next)
    {
        // Debug logging
        Log::info('AdminMiddleware check', [
            'admin_check' => Auth::guard('admin')->check(),
            'admin_id' => Auth::guard('admin')->id(),
            'session_admin_id' => session('admin_id'),
            'url' => $request->url()
        ]);
        
        if (!Auth::guard('admin')->check()) {
            Log::warning('Admin middleware failed - redirecting to admin login');
            return redirect('/admin/login');
        }

        return $next($request);
    }
}