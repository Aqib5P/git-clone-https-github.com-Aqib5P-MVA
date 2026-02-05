<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table("leads", function (Blueprint $table) {
            $table->foreignId("product_id")->nullable()->after("id")->constrained("products")->nullOnDelete();
            $table->foreignId("campaign_id")->nullable()->after("product_id")->constrained("campaigns")->nullOnDelete();
            $table->foreignId("publisher_id")->nullable()->after("campaign_id")->constrained("publishers")->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table("leads", function (Blueprint $table) {
            $table->dropForeign(["product_id"]);
            $table->dropForeign(["campaign_id"]);
            $table->dropForeign(["publisher_id"]);
            $table->dropColumn(["product_id", "campaign_id", "publisher_id"]);
        });
    }
};
