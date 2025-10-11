<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    public function handle($request, Closure $next, $permission)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            abort(403, 'Unauthorized action.');
        }

        $permissions = json_decode($admin->permissions ?? '[]', true);

        if (!in_array($permission, $permissions)) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
