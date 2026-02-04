<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->session()->has("admin_user_id")) {
            if ($request->expectsJson()) {
                return response()->json(["error" => "Unauthenticated"], 401);
            }

            return redirect()->route("login.form");
        }

        return $next($request);
    }
}
