<?php

namespace App\Http\Middleware;

use App\Models\AllowedIp;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIpAllowed
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is("ip/validate") || $request->is("f/*")) {
            return $next($request);
        }

        $ip = $request->ip();
        $allowed = AllowedIp::where("ip", $ip)->exists();

        if (!$allowed) {
            if ($request->expectsJson()) {
                return response()->json(["error" => "IP not allowed", "ip" => $ip], 403);
            }

            return response()->view("errors.ip-blocked", ["ip" => $ip], 403);
        }

        return $next($request);
    }
}
