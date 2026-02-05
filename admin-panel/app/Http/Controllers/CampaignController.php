<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Product;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function index()
    {
        $campaigns = Campaign::with("product")->orderBy("code")->get();
        $products = Product::orderBy("name")->get();
        return view("campaigns.index", ["campaigns" => $campaigns, "products" => $products]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            "code" => ["required", "string", "max:50"],
            "name" => ["required", "string", "max:150"],
            "product_id" => ["required", "integer"],
        ]);

        Campaign::create([
            "code" => strtoupper($data["code"]),
            "name" => $data["name"],
            "product_id" => $data["product_id"],
            "active" => true,
        ]);

        return redirect()->route("campaigns.index");
    }

    public function toggle(Campaign $campaign)
    {
        $campaign->update(["active" => !$campaign->active]);
        return redirect()->route("campaigns.index");
    }
}
