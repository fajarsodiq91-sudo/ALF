<?php

namespace App\Http\Middleware;

use App\Models\TapDevice;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Lets a reader through when it presents an active device token as `Authorization: Bearer <token>`. */
class AuthenticateTapDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        $device = $token ? TapDevice::findByToken($token) : null;

        if (! $device || ! $device->is_active) {
            return response()->json(['ok' => false, 'status' => 'unauthorized', 'message' => 'Device not authorized.'], 401);
        }

        $device->forceFill(['last_seen_at' => now()])->save();
        $request->attributes->set('tap_device', $device);

        return $next($request);
    }
}
