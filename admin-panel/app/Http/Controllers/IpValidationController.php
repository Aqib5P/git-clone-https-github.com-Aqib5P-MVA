<?php

namespace App\Http\Controllers;

use App\Models\AllowedIp;
use Illuminate\Http\Request;

class IpValidationController extends Controller
{
    public function validateIp(Request $request)
    {
        $username = config("admin.username");
        $password = config("admin.password");
        $providedUser = $request->getUser();
        $providedPass = $request->getPassword();

        if (!$username || !$password || $password === "change_me") {
            return response()->json(["error" => "Admin credentials not configured."], 500);
        }

        if ($providedUser !== $username || $providedPass !== $password) {
            return response("Unauthorized", 401)->header("WWW-Authenticate", "Basic realm=\"IP Validation\"");
        }

        $ip = $request->ip();
        $allowed = AllowedIp::firstOrCreate(["ip" => $ip], ["label" => "Validated IP"]);

        return response()->json([
            "ok" => true,
            "ip" => $allowed->ip,
            "created" => $allowed->wasRecentlyCreated,
        ]);
    }
}
