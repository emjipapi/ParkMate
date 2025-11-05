<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRfidApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key');
        $validKey = env('RFID_API_KEY');

        \Log::info('RFID API Key Check', [
            'received_key' => $apiKey,
            'expected_key' => $validKey,
            'match' => $apiKey === $validKey
        ]);

        if (!$apiKey || $apiKey !== $validKey) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Missing or invalid X-API-Key header',
                'debug' => [
                    'received' => $apiKey,
                    'expected' => $validKey
                ]
            ], 401);
        }

        return $next($request);
    }
}
