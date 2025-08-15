<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth; // ✅ Needed for Auth::guard('admin')->check()


class AdminMiddleware
{
    public function handle($request, Closure $next)
    {
        if (!Auth::guard('admin')->check()) {
            return redirect('/login'); // redirect to login if not admin
        }

        return $next($request);
    }
}
