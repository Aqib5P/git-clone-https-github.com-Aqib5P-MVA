<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table("buyers", function (Blueprint $table) {
            $table->string("platform")->default("custom")->after("payload_format");
            $table->json("response_rules")->nullable()->after("headers_json");
            $table->foreignId("default_product_id")->nullable()->after("public_enabled")->constrained("products")->nullOnDelete();
            $table->foreignId("default_campaign_id")->nullable()->after("default_product_id")->constrained("campaigns")->nullOnDelete();
            $table->foreignId("default_publisher_id")->nullable()->after("default_campaign_id")->constrained("publishers")->nullOnDelete();
            $table->decimal("static_payout", 10, 2)->nullable()->after("default_publisher_id");
            $table->string("payout_type")->default("dynamic")->after("static_payout");
            $table->string("payout_model")->default("CPL")->after("payout_type");
            $table->string("payment_terms")->nullable()->after("payout_model");
        });
    }

    public function down(): void
    {
        Schema::table("buyers", function (Blueprint $table) {
            $table->dropForeign(["default_product_id"]);
            $table->dropForeign(["default_campaign_id"]);
            $table->dropForeign(["default_publisher_id"]);
            $table->dropColumn([
                "platform",
                "response_rules",
                "default_product_id",
                "default_campaign_id",
                "default_publisher_id",
                "static_payout",
                "payout_type",
                "payout_model",
                "payment_terms",
            ]);
        });
    }
};
