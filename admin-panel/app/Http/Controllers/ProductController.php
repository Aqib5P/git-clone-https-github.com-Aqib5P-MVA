<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::orderBy("code")->get();
        return view("products.index", ["products" => $products]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            "code" => ["required", "string", "max:50"],
            "name" => ["required", "string", "max:150"],
        ]);

        Product::create([
            "code" => strtoupper($data["code"]),
            "name" => $data["name"],
            "active" => true,
        ]);

        return redirect()->route("products.index");
    }

    public function toggle(Product $product)
    {
        $product->update(["active" => !$product->active]);
        return redirect()->route("products.index");
    }
}
