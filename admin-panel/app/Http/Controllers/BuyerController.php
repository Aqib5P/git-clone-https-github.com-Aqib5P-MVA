<?php

namespace App\Http\Controllers;

use App\Models\Buyer;
use Illuminate\Http\Request;

class BuyerController extends Controller
{
    public function index()
    {
        $buyers = Buyer::orderBy("code")->get();
        return view("buyers.index", ["buyers" => $buyers]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            "code" => ["required", "string", "max:50"],
            "name" => ["required", "string", "max:100"],
            "type" => ["required", "string", "max:50"],
            "active" => ["nullable"],
        ]);

        Buyer::create([
            "code" => strtoupper($data["code"]),
            "name" => $data["name"],
            "type" => $data["type"],
            "active" => $request->boolean("active"),
        ]);

        return redirect()->route("buyers.index");
    }
}
