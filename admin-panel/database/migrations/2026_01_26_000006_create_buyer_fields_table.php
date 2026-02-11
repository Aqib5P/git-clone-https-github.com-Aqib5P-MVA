<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("buyer_fields", function (Blueprint $table) {
            $table->id();
            $table->foreignId("buyer_id")->constrained("buyers")->cascadeOnDelete();
            $table->string("direction")->default("single");
            $table->string("field_name");
            $table->string("source_type")->default("lead");
            $table->string("source_key")->nullable();
            $table->text("source_value")->nullable();
            $table->boolean("required")->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("buyer_fields");
    }
};
