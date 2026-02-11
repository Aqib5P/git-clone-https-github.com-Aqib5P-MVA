<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("attempts", function (Blueprint $table) {
            $table->id();
            $table->foreignId("lead_id")->constrained("leads")->cascadeOnDelete();
            $table->foreignId("buyer_id")->nullable()->constrained("buyers")->nullOnDelete();
            $table->string("endpoint")->nullable();
            $table->string("direction")->default("single");
            $table->string("status")->default("unknown");
            $table->unsignedInteger("http_status")->nullable();
            $table->string("ping_id")->nullable();
            $table->string("forwarding_number")->nullable();
            $table->decimal("payout", 10, 2)->nullable();
            $table->decimal("bid_amount", 10, 2)->nullable();
            $table->json("payload_json")->nullable();
            $table->json("response_json")->nullable();
            $table->longText("response_raw")->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("attempts");
    }
};
