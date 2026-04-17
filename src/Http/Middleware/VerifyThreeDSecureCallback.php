<?php

declare(strict_types=1);

namespace TruePos\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to verify that 3DS callbacks come from expected bank IPs.
 * Optional — can be applied via config.
 */
final class VerifyThreeDSecureCallback
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowedIps = config('truepos.allowed_callback_ips', []);

        if (! empty($allowedIps) && ! in_array($request->ip(), $allowedIps, true)) {
            abort(403, 'Unauthorized 3D Secure callback source.');
        }

        return $next($request);
    }
}
