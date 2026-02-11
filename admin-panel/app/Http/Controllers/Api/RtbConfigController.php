<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\BuyerField;
use App\Models\LeadField;

class RtbConfigController extends Controller
{
    public function index()
    {
        $buyers = Buyer::query()
            ->where("active", true)
            ->where("scope", "rtb")
            ->orderBy("code")
            ->get(["id", "code", "name", "type", "scope"]);

        $required = BuyerField::query()
            ->where("required", true)
            ->where("source_type", "lead")
            ->whereIn("buyer_id", $buyers->pluck("id"))
            ->get()
            ->groupBy("buyer_id");

        $requirements = [];
        foreach ($buyers as $buyer) {
            $fields = $required->get($buyer->id, collect())
                ->map(function ($field) {
                    return $field->source_key ?: $field->field_name;
                })
                ->unique()
                ->values()
                ->all();
            $requirements[$buyer->code] = $fields;
        }

        $leadFieldsCollection = LeadField::where("active", true)
            ->orderBy("key")
            ->get();

        if ($leadFieldsCollection->isEmpty()) {
            $leadFields = collect(config("lead_fields"))
                ->map(function ($meta) {
                    return [
                        "label" => $meta["label"] ?? "",
                        "type" => $meta["type"] ?? "text",
                        "options" => $meta["options"] ?? null,
                    ];
                });
        } else {
            $leadFields = $leadFieldsCollection->mapWithKeys(function ($field) {
                return [$field->key => [
                    "label" => $field->label,
                    "type" => $field->type,
                    "options" => $field->options,
                ]];
            });
        }

        $response = response()->json([
            "buyers" => $buyers,
            "fields" => $leadFields,
            "requirements" => $requirements,
        ]);

        return $response->header("Access-Control-Allow-Origin", "*");
    }
}
