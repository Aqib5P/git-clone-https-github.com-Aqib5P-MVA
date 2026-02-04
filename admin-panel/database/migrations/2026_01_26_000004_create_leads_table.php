<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("leads", function (Blueprint $table) {
            $table->id();
            $table->string("first_name")->nullable();
            $table->string("last_name")->nullable();
            $table->string("email")->nullable();
            $table->string("phone")->nullable();
            $table->string("zip5")->nullable();
            $table->string("city")->nullable();
            $table->string("state")->nullable();
            $table->string("accident_state")->nullable();
            $table->string("ip_address")->nullable();
            $table->string("source_url")->nullable();
            $table->string("cert_id")->nullable();
            $table->string("cert_url")->nullable();
            $table->json("lead_json")->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("leads");
    }
};
