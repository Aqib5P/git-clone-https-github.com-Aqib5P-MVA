<?php

namespace App\Services;

use App\Models\Buyer;
use App\Models\BuyerField;

class BuyerTemplateService
{
    public function applyTemplate(Buyer $buyer, string $templateKey, bool $replace = false): void
    {
        $templates = config("buyer_templates");
        if (!isset($templates[$templateKey])) return;

        $fields = $templates[$templateKey]["fields"] ?? [];
        if ($replace) {
            BuyerField::where("buyer_id", $buyer->id)->delete();
        }

        foreach ($fields as $item) {
            [$fieldName, $sourceType, $sourceKey, $sourceValue, $direction, $required] = $item;
            BuyerField::firstOrCreate([
                "buyer_id" => $buyer->id,
                "field_name" => $fieldName,
                "direction" => $direction,
                "source_type" => $sourceType,
                "source_key" => $sourceKey,
                "source_value" => $sourceValue,
            ], [
                "required" => (bool) $required,
            ]);
        }
    }
}
