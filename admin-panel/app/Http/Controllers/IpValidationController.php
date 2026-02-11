<?php

namespace App\Http\Controllers;

use App\Models\AllowedIp;
use Illuminate\Http\Request;

class IpValidationController extends Controller
{
    public function show()
    {
        return view("auth.ip-validate");
    }

    public function validateIp(Request $request)
    {
        $username = config("admin.username");
        $password = config("admin.password");
        $providedUser = $request->input("username");
        $providedPass = $request->input("password");

        if (!$username || !$password || $password === "change_me") {
            return response()->json(["error" => "Admin credentials not configured."], 500);
        }

        if ($providedUser !== $username || $providedPass !== $password) {
            return back()->withErrors(["username" => "Invalid credentials."])->withInput();
        }

        $ip = $request->ip();
        $allowed = AllowedIp::firstOrCreate(["ip" => $ip], ["label" => "Validated IP"]);

        return view("auth.ip-success", [
            "ip" => $allowed->ip,
            "created" => $allowed->wasRecentlyCreated,
        ]);
    }
}
