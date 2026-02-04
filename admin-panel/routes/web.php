<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BuyerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IpValidationController;
use App\Http\Controllers\LeadFieldController;
use App\Http\Controllers\PublicFormController;
use Illuminate\Support\Facades\Route;

Route::get("/ip/validate", [IpValidationController::class, "validateIp"])->name("ip.validate");
Route::get("/f/{token}", [PublicFormController::class, "show"])->name("forms.public");
Route::post("/f/{token}", [PublicFormController::class, "submit"])->name("forms.submit");

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
        Route::post("/buyers/{buyer}/token", [BuyerController::class, "regenerateToken"])->name("buyers.token");
        Route::post("/buyers/{buyer}/fields", [BuyerController::class, "storeField"])->name("buyers.fields.store");
        Route::post("/buyers/fields/{field}/update", [BuyerController::class, "updateField"])->name("buyers.fields.update");
        Route::post("/buyers/fields/{field}/delete", [BuyerController::class, "deleteField"])->name("buyers.fields.delete");

        Route::get("/fields", [LeadFieldController::class, "index"])->name("fields.index");
        Route::post("/fields", [LeadFieldController::class, "store"])->name("fields.store");
        Route::post("/fields/{field}/toggle", [LeadFieldController::class, "toggle"])->name("fields.toggle");
    });
});
