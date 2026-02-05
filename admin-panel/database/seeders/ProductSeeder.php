<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $product = Product::updateOrCreate(
            ["code" => "MVA"],
            ["name" => "Motor Vehicle Accident", "active" => true]
        );

        Campaign::updateOrCreate(
            ["code" => "MVA-DEFAULT"],
            ["name" => "MVA Default", "product_id" => $product->id, "active" => true]
        );
    }
}
