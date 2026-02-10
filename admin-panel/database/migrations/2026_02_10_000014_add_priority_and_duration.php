<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table("buyers", function (Blueprint $table) {
            $table->unsignedInteger("priority")->default(100)->after("notes");
        });

        Schema::table("attempts", function (Blueprint $table) {
            $table->decimal("duration", 10, 2)->nullable()->after("bid_amount");
        });
    }

    public function down(): void
    {
        Schema::table("attempts", function (Blueprint $table) {
            $table->dropColumn("duration");
        });

        Schema::table("buyers", function (Blueprint $table) {
            $table->dropColumn("priority");
        });
    }
};
