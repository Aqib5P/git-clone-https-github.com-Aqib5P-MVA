<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Buyer;

trait RecordSourceHelper
{
    protected function recordSources(?Buyer $buyer): array
    {
        $rules = $buyer?->response_rules ?? [];
        if (!is_array($rules)) return [];
        $sources = $rules["record_source"] ?? [];
        return is_array($sources) ? $sources : [];
    }

    protected function pickRecordValue(
        array $sources,
        string $field,
        array $values,
        array $defaultOrder,
        bool $treatUnknownAsNull = false,
        bool $treatEmptyStringAsNull = false
    ) {
        $preferred = $sources[$field] ?? "auto";
        $order = $this->orderForRecordSource($preferred, $defaultOrder);
        foreach ($order as $key) {
            if (!array_key_exists($key, $values)) continue;
            $value = $values[$key];
            if ($treatUnknownAsNull && is_string($value) && strtolower($value) === "unknown") {
                continue;
            }
            if ($treatEmptyStringAsNull && $value === "") {
                continue;
            }
            if ($value !== null) return $value;
        }
        return null;
    }

    protected function orderForRecordSource(?string $source, array $defaultOrder): array
    {
        $source = is_string($source) ? strtolower(trim($source)) : "auto";
        if ($source === "ping") {
            $order = ["ping", "post", "single"];
        } elseif ($source === "post") {
            $order = ["post", "ping", "single"];
        } elseif ($source === "single") {
            $order = ["single", "post", "ping"];
        } else {
            $order = $defaultOrder;
        }

        $seen = [];
        $result = [];
        foreach ($order as $item) {
            if (isset($seen[$item])) continue;
            $seen[$item] = true;
            $result[] = $item;
        }
        return $result;
    }
}
