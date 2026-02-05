<?php

namespace App\Http\Controllers;

use App\Models\Publisher;
use Illuminate\Http\Request;

class PublisherController extends Controller
{
    public function index()
    {
        $publishers = Publisher::orderBy("code")->get();
        return view("publishers.index", ["publishers" => $publishers]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            "code" => ["required", "string", "max:50"],
            "name" => ["required", "string", "max:150"],
        ]);

        Publisher::create([
            "code" => strtoupper($data["code"]),
            "name" => $data["name"],
            "active" => true,
        ]);

        return redirect()->route("publishers.index");
    }

    public function toggle(Publisher $publisher)
    {
        $publisher->update(["active" => !$publisher->active]);
        return redirect()->route("publishers.index");
    }
}
