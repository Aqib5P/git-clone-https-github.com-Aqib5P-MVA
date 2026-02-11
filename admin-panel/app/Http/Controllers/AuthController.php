<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view("auth.login");
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            "username" => ["required", "string"],
            "password" => ["required", "string"],
        ]);

        $user = AdminUser::where("username", $data["username"])->first();
        if (!$user || !Hash::check($data["password"], $user->password)) {
            return back()->withErrors(["username" => "Invalid credentials."])->withInput();
        }

        $request->session()->put("admin_user_id", $user->id);
        $request->session()->regenerate();

        return redirect()->route("dashboard");
    }

    public function logout(Request $request)
    {
        $request->session()->forget("admin_user_id");
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route("login.form");
    }
}
