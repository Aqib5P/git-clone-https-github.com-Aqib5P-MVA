<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("lead_fields", function (Blueprint $table) {
            $table->id();
            $table->string("key")->unique();
            $table->string("label");
            $table->string("type")->default("text");
            $table->json("options")->nullable();
            $table->boolean("active")->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("lead_fields");
    }
};
