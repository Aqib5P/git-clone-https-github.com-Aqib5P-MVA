<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BuyerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IpValidationController;
use App\Http\Controllers\LeadFieldController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\PublisherController;
use App\Http\Controllers\PublicFormController;
use Illuminate\Support\Facades\Route;

Route::get("/ip/validate", [IpValidationController::class, "show"])->name("ip.validate");
Route::post("/ip/validate", [IpValidationController::class, "validateIp"])->name("ip.validate.submit");
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
        Route::post("/buyers/{buyer}/template", [BuyerController::class, "applyTemplate"])->name("buyers.template");
        Route::post("/buyers/{buyer}/test", [BuyerController::class, "test"])->name("buyers.test");
        Route::post("/buyers/{buyer}/test-ping", [BuyerController::class, "testPing"])->name("buyers.test.ping");
        Route::post("/buyers/{buyer}/test-post", [BuyerController::class, "testPost"])->name("buyers.test.post");
        Route::post("/buyers/{buyer}/fields", [BuyerController::class, "storeField"])->name("buyers.fields.store");
        Route::post("/buyers/fields/{field}/update", [BuyerController::class, "updateField"])->name("buyers.fields.update");
        Route::post("/buyers/fields/{field}/delete", [BuyerController::class, "deleteField"])->name("buyers.fields.delete");

        Route::get("/fields", [LeadFieldController::class, "index"])->name("fields.index");
        Route::post("/fields", [LeadFieldController::class, "store"])->name("fields.store");
        Route::post("/fields/{field}/toggle", [LeadFieldController::class, "toggle"])->name("fields.toggle");

        Route::get("/products", [ProductController::class, "index"])->name("products.index");
        Route::post("/products", [ProductController::class, "store"])->name("products.store");
        Route::post("/products/{product}/toggle", [ProductController::class, "toggle"])->name("products.toggle");

        Route::get("/campaigns", [CampaignController::class, "index"])->name("campaigns.index");
        Route::post("/campaigns", [CampaignController::class, "store"])->name("campaigns.store");
        Route::post("/campaigns/{campaign}/toggle", [CampaignController::class, "toggle"])->name("campaigns.toggle");

        Route::get("/publishers", [PublisherController::class, "index"])->name("publishers.index");
        Route::post("/publishers", [PublisherController::class, "store"])->name("publishers.store");
        Route::post("/publishers/{publisher}/toggle", [PublisherController::class, "toggle"])->name("publishers.toggle");

        Route::get("/leads", [LeadController::class, "index"])->name("leads.index");
    });
});
