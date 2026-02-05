<?php

use App\Http\Controllers\Api\BuyerSubmitController;
use App\Http\Controllers\Api\FormConfigController;
use App\Http\Controllers\Api\LeadIntakeController;
use App\Http\Controllers\Api\RtbConfigController;
use App\Http\Controllers\Api\RtbSubmitController;
use Illuminate\Support\Facades\Route;

Route::post("/lead-intake", [LeadIntakeController::class, "store"]);
Route::get("/form-config", [FormConfigController::class, "index"]);
Route::post("/buyer-submit", [BuyerSubmitController::class, "submit"]);
Route::get("/rtb-config", [RtbConfigController::class, "index"]);
Route::post("/rtb-submit", [RtbSubmitController::class, "submit"]);
Route::options("/buyer-submit", function () {
    return response()->json([])->header("Access-Control-Allow-Origin", "*")
        ->header("Access-Control-Allow-Headers", "Content-Type")
        ->header("Access-Control-Allow-Methods", "POST, OPTIONS");
});
Route::options("/rtb-submit", function () {
    return response()->json([])->header("Access-Control-Allow-Origin", "*")
        ->header("Access-Control-Allow-Headers", "Content-Type")
        ->header("Access-Control-Allow-Methods", "POST, OPTIONS");
});
