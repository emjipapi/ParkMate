<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\PageView;

class TrackPageView
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Avoid tracking analytics route itself (to prevent inflating)
        if (!$request->is('analytics')) {
            PageView::create([
                'url' => $request->path(),
                'ip_address' => $request->ip(),
            ]);
        }

        return $next($request);
    }
}
