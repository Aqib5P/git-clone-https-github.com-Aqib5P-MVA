<?php

namespace App\Http\Controllers;

use App\Models\Attempt;
use App\Models\Buyer;
use App\Models\Lead;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $start = $request->query("start_date");
        $end = $request->query("end_date");
        $status = $request->query("status");
        $buyer = $request->query("buyer");

        $startDate = $start ? Carbon::parse($start)->startOfDay() : Carbon::today()->startOfDay();
        $endDate = $end ? Carbon::parse($end)->endOfDay() : Carbon::today()->endOfDay();

        $baseQuery = Attempt::query()
            ->with(["lead", "buyer"])
            ->whereBetween("created_at", [$startDate, $endDate]);

        if ($status) {
            $baseQuery->where("status", $status);
        }

        if ($buyer) {
            $baseQuery->where("endpoint", $buyer);
        }

        $attempts = (clone $baseQuery)
            ->orderByDesc("created_at")
            ->limit(100)
            ->get();

        $statusCounts = (clone $baseQuery)
            ->selectRaw("status, count(*) as total")
            ->groupBy("status")
            ->pluck("total", "status")
            ->toArray();

        $buyerStats = Attempt::query()
            ->selectRaw("endpoint, count(*) as total,
                sum(case when status = 'accepted' then 1 else 0 end) as accepted,
                sum(case when status = 'rejected' then 1 else 0 end) as rejected,
                sum(payout) as total_payout,
                max(payout) as max_payout")
            ->whereBetween("created_at", [$startDate, $endDate])
            ->when($status, fn ($q) => $q->where("status", $status))
            ->when($buyer, fn ($q) => $q->where("endpoint", $buyer))
            ->groupBy("endpoint")
            ->orderBy("endpoint")
            ->get();

        $topBuyer = $buyerStats->sortByDesc("accepted")->first();
        $topPayoutBuyer = $buyerStats->sortByDesc("max_payout")->first();
        $totalAttempts = array_sum($statusCounts);
        $accepted = $statusCounts["accepted"] ?? 0;
        $rejected = $statusCounts["rejected"] ?? 0;
        $acceptRate = $totalAttempts > 0 ? round(($accepted / $totalAttempts) * 100, 1) : 0;
        $totalRevenue = Attempt::query()
            ->whereBetween("created_at", [$startDate, $endDate])
            ->when($status, fn ($q) => $q->where("status", $status))
            ->when($buyer, fn ($q) => $q->where("endpoint", $buyer))
            ->sum("payout");
        $rpm = $totalAttempts > 0 ? round(($totalRevenue / $totalAttempts) * 1000, 2) : 0;
        $duplicateCount = Attempt::query()
            ->whereBetween("created_at", [$startDate, $endDate])
            ->when($status, fn ($q) => $q->where("status", $status))
            ->when($buyer, fn ($q) => $q->where("endpoint", $buyer))
            ->where("is_duplicate", true)
            ->count();
        $duplicateRate = $totalAttempts > 0 ? round(($duplicateCount / $totalAttempts) * 100, 1) : 0;

        $buyers = Buyer::orderBy("code")->get();

        $productStats = Lead::query()
            ->with("product")
            ->selectRaw("product_id, count(*) as total")
            ->whereBetween("created_at", [$startDate, $endDate])
            ->groupBy("product_id")
            ->orderByDesc("total")
            ->get();

        $campaignStats = Lead::query()
            ->with("campaign")
            ->selectRaw("campaign_id, count(*) as total")
            ->whereBetween("created_at", [$startDate, $endDate])
            ->groupBy("campaign_id")
            ->orderByDesc("total")
            ->get();

        $publisherStats = Lead::query()
            ->with("publisher")
            ->selectRaw("publisher_id, count(*) as total")
            ->whereBetween("created_at", [$startDate, $endDate])
            ->groupBy("publisher_id")
            ->orderByDesc("total")
            ->get();

        return view("dashboard", [
            "attempts" => $attempts,
            "statusCounts" => $statusCounts,
            "buyerStats" => $buyerStats,
            "buyers" => $buyers,
            "acceptRate" => $acceptRate,
            "topBuyer" => $topBuyer,
            "topPayoutBuyer" => $topPayoutBuyer,
            "totalRevenue" => $totalRevenue,
            "rpm" => $rpm,
            "duplicateCount" => $duplicateCount,
            "duplicateRate" => $duplicateRate,
            "productStats" => $productStats,
            "campaignStats" => $campaignStats,
            "publisherStats" => $publisherStats,
            "filters" => [
                "start_date" => $startDate->toDateString(),
                "end_date" => $endDate->toDateString(),
                "status" => $status,
                "buyer" => $buyer,
            ],
        ]);
    }
}
