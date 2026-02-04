<?php

namespace App\Http\Controllers;

use App\Models\Buyer;
use App\Models\BuyerField;
use App\Models\LeadField;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
            "scope" => ["required", "string", "max:50"],
            "payload_format" => ["required", "string", "max:20"],
            "ping_url" => ["nullable", "string"],
            "post_url" => ["nullable", "string"],
            "active" => ["nullable"],
        ]);

        $buyer = Buyer::create([
            "code" => strtoupper($data["code"]),
            "name" => $data["name"],
            "type" => $data["type"],
            "scope" => $data["scope"],
            "payload_format" => $data["payload_format"],
            "ping_url" => $data["ping_url"] ?? null,
            "post_url" => $data["post_url"] ?? null,
            "active" => $request->boolean("active"),
        ]);

        if ($request->boolean("public_enabled")) {
            $buyer->update([
                "public_enabled" => true,
                "public_token" => $buyer->public_token ?: strtoupper($buyer->code) . "-" . Str::random(8),
            ]);
        }

        return redirect()->route("buyers.edit", $buyer);
    }

    public function edit(Buyer $buyer)
    {
        $fields = $buyer->fields()->orderBy("field_name")->get();
        $leadFields = LeadField::orderBy("key")->get();

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
            "scope" => ["required", "string", "max:50"],
            "payload_format" => ["required", "string", "max:20"],
            "ping_url" => ["nullable", "string"],
            "post_url" => ["nullable", "string"],
            "headers_json" => ["nullable", "string"],
            "active" => ["nullable"],
        ]);

        $buyer->update([
            "name" => $data["name"],
            "type" => $data["type"],
            "scope" => $data["scope"],
            "payload_format" => $data["payload_format"],
            "ping_url" => $data["ping_url"] ?? null,
            "post_url" => $data["post_url"] ?? null,
            "headers_json" => $data["headers_json"] ? json_decode($data["headers_json"], true) : null,
            "active" => $request->boolean("active"),
            "public_enabled" => $request->boolean("public_enabled"),
        ]);

        if ($request->boolean("public_enabled") && !$buyer->public_token) {
            $buyer->update([
                "public_token" => strtoupper($buyer->code) . "-" . Str::random(8),
            ]);
        }

        return redirect()->route("buyers.edit", $buyer);
    }

    public function toggle(Buyer $buyer)
    {
        $buyer->update(["active" => !$buyer->active]);
        return redirect()->route("buyers.index");
    }

    public function regenerateToken(Buyer $buyer)
    {
        $buyer->update([
            "public_token" => strtoupper($buyer->code) . "-" . Str::random(8),
            "public_enabled" => true,
        ]);

        return redirect()->route("buyers.edit", $buyer);
    }

    public function storeField(Request $request, Buyer $buyer)
    {
        $data = $request->validate([
            "field_name" => ["required", "string", "max:100"],
            "direction" => ["required", "string", "max:20"],
            "source_type" => ["required", "string", "max:20"],
            "source_key" => ["nullable", "string", "max:100"],
            "source_value" => ["nullable", "string"],
            "required" => ["nullable"],
        ]);

        BuyerField::create([
            "buyer_id" => $buyer->id,
            "direction" => $data["direction"],
            "field_name" => $data["field_name"],
            "source_type" => $data["source_type"],
            "source_key" => $data["source_key"] ?? ($data["source_type"] === "lead" ? $data["field_name"] : null),
            "source_value" => $data["source_value"] ?? null,
            "required" => $request->boolean("required"),
        ]);

        return redirect()->route("buyers.edit", $buyer);
    }

    public function updateField(Request $request, BuyerField $field)
    {
        $data = $request->validate([
            "required" => ["nullable"],
            "direction" => ["nullable", "string", "max:20"],
            "source_type" => ["nullable", "string", "max:20"],
            "source_key" => ["nullable", "string", "max:100"],
            "source_value" => ["nullable", "string"],
        ]);

        if (array_key_exists("required", $data)) {
            $field->required = $request->boolean("required");
        }
        if (isset($data["direction"])) {
            $field->direction = $data["direction"];
        }
        if (isset($data["source_type"])) {
            $field->source_type = $data["source_type"];
        }
        if (array_key_exists("source_key", $data)) {
            $field->source_key = $data["source_key"] ?: null;
        }
        if (array_key_exists("source_value", $data)) {
            $field->source_value = $data["source_value"] ?: null;
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
