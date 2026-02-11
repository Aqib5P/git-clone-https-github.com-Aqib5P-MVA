<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table("attempts", function (Blueprint $table) {
            $table->boolean("is_duplicate")->default(false)->after("status");
            $table->foreignId("duplicate_of_id")->nullable()->after("is_duplicate")->constrained("attempts")->nullOnDelete();
            $table->string("duplicate_window")->nullable()->after("duplicate_of_id");
        });
    }

    public function down(): void
    {
        Schema::table("attempts", function (Blueprint $table) {
            $table->dropForeign(["duplicate_of_id"]);
            $table->dropColumn(["is_duplicate", "duplicate_of_id", "duplicate_window"]);
        });
    }
};
