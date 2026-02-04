<?php

namespace App\Http\Controllers;

use App\Models\Buyer;
use App\Models\BuyerField;
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

    public function edit(Buyer $buyer)
    {
        $fields = $buyer->fields()->orderBy("field_name")->get();
        $leadFields = config("lead_fields");

        return view("buyers.edit", [
            "buyer" => $buyer,
            "fields" => $fields,
            "leadFields" => $leadFields,
        ]);
    }

    public function update(Request $request, Buyer $buyer)
    {
        $data = $request->validate([
            "name" => ["required", "string", "max:100"],
            "type" => ["required", "string", "max:50"],
            "active" => ["nullable"],
        ]);

        $buyer->update([
            "name" => $data["name"],
            "type" => $data["type"],
            "active" => $request->boolean("active"),
        ]);

        return redirect()->route("buyers.edit", $buyer);
    }

    public function toggle(Buyer $buyer)
    {
        $buyer->update(["active" => !$buyer->active]);
        return redirect()->route("buyers.index");
    }

    public function storeField(Request $request, Buyer $buyer)
    {
        $data = $request->validate([
            "field_name" => ["required", "string", "max:100"],
            "direction" => ["required", "string", "max:20"],
            "required" => ["nullable"],
        ]);

        BuyerField::create([
            "buyer_id" => $buyer->id,
            "direction" => $data["direction"],
            "field_name" => $data["field_name"],
            "source_type" => "lead",
            "required" => $request->boolean("required"),
        ]);

        return redirect()->route("buyers.edit", $buyer);
    }

    public function updateField(Request $request, BuyerField $field)
    {
        $data = $request->validate([
            "required" => ["nullable"],
            "direction" => ["nullable", "string", "max:20"],
        ]);

        if (array_key_exists("required", $data)) {
            $field->required = $request->boolean("required");
        }
        if (isset($data["direction"])) {
            $field->direction = $data["direction"];
        }
        $field->save();

        return redirect()->route("buyers.edit", $field->buyer_id);
    }

    public function deleteField(BuyerField $field)
    {
        $buyerId = $field->buyer_id;
        $field->delete();

        return redirect()->route("buyers.edit", $buyerId);
    }
}
