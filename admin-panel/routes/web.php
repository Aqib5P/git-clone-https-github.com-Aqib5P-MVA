<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BuyerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IpValidationController;
use Illuminate\Support\Facades\Route;

Route::get("/ip/validate", [IpValidationController::class, "validateIp"])->name("ip.validate");

Route::middleware(["ip.allow"])->group(function () {
    Route::get("/login", [AuthController::class, "showLogin"])->name("login.form");
    Route::post("/login", [AuthController::class, "login"])->name("login.submit");
    Route::post("/logout", [AuthController::class, "logout"])->name("logout");

    Route::middleware(["admin.auth"])->group(function () {
        Route::get("/", [DashboardController::class, "index"])->name("dashboard");
        Route::get("/buyers", [BuyerController::class, "index"])->name("buyers.index");
        Route::post("/buyers", [BuyerController::class, "store"])->name("buyers.store");
        Route::get("/buyers/{buyer}", [BuyerController::class, "edit"])->name("buyers.edit");
        Route::post("/buyers/{buyer}/update", [BuyerController::class, "update"])->name("buyers.update");
        Route::post("/buyers/{buyer}/toggle", [BuyerController::class, "toggle"])->name("buyers.toggle");
        Route::post("/buyers/{buyer}/fields", [BuyerController::class, "storeField"])->name("buyers.fields.store");
        Route::post("/buyers/fields/{field}/update", [BuyerController::class, "updateField"])->name("buyers.fields.update");
        Route::post("/buyers/fields/{field}/delete", [BuyerController::class, "deleteField"])->name("buyers.fields.delete");
    });
});
