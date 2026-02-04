<?php

use App\Http\Controllers\Api\FormConfigController;
use App\Http\Controllers\Api\LeadIntakeController;
use Illuminate\Support\Facades\Route;

Route::post("/lead-intake", [LeadIntakeController::class, "store"]);
Route::get("/form-config", [FormConfigController::class, "index"]);
