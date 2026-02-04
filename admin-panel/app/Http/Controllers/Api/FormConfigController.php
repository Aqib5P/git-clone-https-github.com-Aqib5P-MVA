<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\BuyerField;

class FormConfigController extends Controller
{
    public function index()
    {
        $buyers = Buyer::orderBy("code")
            ->get(["code", "name", "active", "type"]);

        $required = BuyerField::query()
            ->where("required", true)
            ->where("source_type", "lead")
            ->get()
            ->groupBy("buyer_id");

        $requirements = [];
        foreach ($buyers as $buyer) {
            $fields = $required->get($buyer->id, collect())
                ->pluck("field_name")
                ->unique()
                ->values()
                ->all();
            $requirements[$buyer->code] = $fields;
        }

        $response = response()->json([
            "buyers" => $buyers,
            "fields" => config("lead_fields"),
            "requirements" => $requirements,
        ]);

        return $response->header("Access-Control-Allow-Origin", "*");
    }
}
