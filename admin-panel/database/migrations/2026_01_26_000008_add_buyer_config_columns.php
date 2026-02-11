<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table("buyers", function (Blueprint $table) {
            $table->string("scope")->default("single")->after("type");
            $table->string("payload_format")->default("form")->after("scope");
            $table->string("ping_url")->nullable()->after("payload_format");
            $table->string("post_url")->nullable()->after("ping_url");
            $table->json("headers_json")->nullable()->after("post_url");
            $table->string("public_token")->nullable()->after("headers_json");
            $table->boolean("public_enabled")->default(false)->after("public_token");
        });
    }

    public function down(): void
    {
        Schema::table("buyers", function (Blueprint $table) {
            $table->dropColumn([
                "scope",
                "payload_format",
                "ping_url",
                "post_url",
                "headers_json",
                "public_token",
                "public_enabled",
            ]);
        });
    }
};
