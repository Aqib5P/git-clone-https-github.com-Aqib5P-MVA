<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("rtb_bids", function (Blueprint $table) {
            $table->id();
            $table->foreignId("attempt_id")->nullable()->constrained("attempts")->nullOnDelete();
            $table->foreignId("buyer_id")->nullable()->constrained("buyers")->nullOnDelete();
            $table->string("bid_id")->nullable();
            $table->decimal("bid_amount", 10, 2)->nullable();
            $table->string("phone_number")->nullable();
            $table->string("sip_address")->nullable();
            $table->timestamp("expires_at")->nullable();
            $table->json("bid_json")->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("rtb_bids");
    }
};
