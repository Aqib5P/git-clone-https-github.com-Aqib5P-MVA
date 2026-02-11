<?php

namespace App\Services;

use App\Models\Attempt;
use Carbon\Carbon;

class DuplicateChecker
{
    public function findDuplicateAttempt(int $buyerId, ?string $phone): ?Attempt
    {
        if (!$phone) return null;

        $windowDays = (int) config("admin.duplicate_window_days", 30);
        $since = Carbon::now()->subDays($windowDays);

        return Attempt::query()
            ->where("buyer_id", $buyerId)
            ->whereHas("lead", function ($query) use ($phone) {
                $query->where("phone", $phone);
            })
            ->where("created_at", ">=", $since)
            ->orderByDesc("created_at")
            ->first();
    }
}
