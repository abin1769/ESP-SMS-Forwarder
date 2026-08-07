<?php

namespace App\Http\Middleware;

use App\Models\Device;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureValidDeviceToken
{
    /**
     * Handle an incoming request for ESP32 Device API calls.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Device-Token') 
            ?? $request->bearerToken() 
            ?? $request->input('token');

        if (blank($token)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Device token missing.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $device = Device::where('token', $token)->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Invalid device token.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Attach resolved device to request attributes for downstream controllers/services
        $request->attributes->set('authenticated_device', $device);

        return $next($request);
    }
}
