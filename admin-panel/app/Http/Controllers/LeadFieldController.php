<?php

namespace App\Http\Controllers;

use App\Models\LeadField;
use Illuminate\Http\Request;

class LeadFieldController extends Controller
{
    public function index()
    {
        $fields = LeadField::orderBy("key")->get();
        return view("fields.index", ["fields" => $fields]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            "key" => ["required", "string", "max:100"],
            "label" => ["required", "string", "max:150"],
            "type" => ["required", "string", "max:20"],
            "options" => ["nullable", "string"],
        ]);

        LeadField::create([
            "key" => $data["key"],
            "label" => $data["label"],
            "type" => $data["type"],
            "options" => $data["options"] ? json_decode($data["options"], true) : null,
            "active" => true,
        ]);

        return redirect()->route("fields.index");
    }

    public function toggle(LeadField $field)
    {
        $field->update(["active" => !$field->active]);
        return redirect()->route("fields.index");
    }
}
