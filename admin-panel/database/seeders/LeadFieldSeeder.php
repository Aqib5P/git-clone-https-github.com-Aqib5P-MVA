<?php

namespace Database\Seeders;

use App\Models\LeadField;
use Illuminate\Database\Seeder;

class LeadFieldSeeder extends Seeder
{
    public function run(): void
    {
        $fields = config("lead_fields");
        if (!is_array($fields)) {
            return;
        }

        foreach ($fields as $key => $meta) {
            LeadField::updateOrCreate(
                ["key" => $key],
                [
                    "label" => $meta["label"] ?? $key,
                    "type" => $meta["type"] ?? "text",
                    "options" => $meta["options"] ?? null,
                    "active" => true,
                ]
            );
        }
    }
}
