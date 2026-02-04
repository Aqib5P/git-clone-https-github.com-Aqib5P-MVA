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
    });
});
