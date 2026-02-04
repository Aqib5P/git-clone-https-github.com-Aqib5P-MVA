<?php

use App\Http\Controllers\Api\LeadIntakeController;
use Illuminate\Support\Facades\Route;

Route::post("/lead-intake", [LeadIntakeController::class, "store"]);
