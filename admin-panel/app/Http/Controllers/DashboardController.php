<?php

namespace App\Http\Controllers;

use App\Models\Attempt;
use App\Models\Buyer;
use App\Models\Lead;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $leadStatusSub = Attempt::query()
            ->selectRaw("lead_id,
                max(case when status = 'accepted' then 1 else 0 end) as has_accepted,
                max(case when status = 'rejected' then 1 else 0 end) as has_rejected")
            ->whereBetween("created_at", [$startDate, $endDate])
            ->when($buyer, fn ($q) => $q->where("endpoint", $buyer))
            ->groupBy("lead_id");

        $leadCountsRow = DB::query()
            ->fromSub($leadStatusSub, "ls")
            ->selectRaw("count(*) as total,
                sum(case when has_accepted = 1 then 1 else 0 end) as accepted,
                sum(case when has_accepted = 0 and has_rejected = 1 then 1 else 0 end) as rejected,
                sum(case when has_accepted = 0 and has_rejected = 0 then 1 else 0 end) as unknown")
            ->first();

        $accepted = (int) ($leadCountsRow->accepted ?? 0);
        $rejected = (int) ($leadCountsRow->rejected ?? 0);
        $unknown = (int) ($leadCountsRow->unknown ?? 0);
        $total = (int) ($leadCountsRow->total ?? 0);

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
        $topPayoutBuyer = $buyerStats->sortByDesc("total_payout")->first();
        $acceptRate = $total > 0 ? round(($accepted / $total) * 100, 1) : 0;
        $rejectRate = $total > 0 ? round(($rejected / $total) * 100, 1) : 0;
        $totalRevenue = Attempt::query()
            ->whereBetween("created_at", [$startDate, $endDate])
            ->when($status, fn ($q) => $q->where("status", $status))
            ->when($buyer, fn ($q) => $q->where("endpoint", $buyer))
            ->sum("payout");
        $duplicateAttemptCount = Attempt::query()
            ->whereBetween("created_at", [$startDate, $endDate])
            ->when($status, fn ($q) => $q->where("status", $status))
            ->when($buyer, fn ($q) => $q->where("endpoint", $buyer))
            ->where("is_duplicate", true)
            ->count();
        $duplicateLeadCount = Lead::query()
            ->whereBetween("created_at", [$startDate, $endDate])
            ->when($buyer, function ($query) use ($buyer) {
                $query->whereHas("attempts", function ($attemptQuery) use ($buyer) {
                    $attemptQuery->where("endpoint", $buyer);
                });
            })
            ->whereNotNull("phone")
            ->selectRaw("phone, count(*) as cnt")
            ->groupBy("phone")
            ->havingRaw("count(*) > 1")
            ->get()
            ->sum(fn ($row) => (int) $row->cnt - 1);
        $duplicateRate = $total > 0 ? round(($duplicateLeadCount / $total) * 100, 1) : 0;

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
            "buyerStats" => $buyerStats,
            "buyers" => $buyers,
            "accepted" => $accepted,
            "rejected" => $rejected,
            "unknown" => $unknown,
            "total" => $total,
            "acceptRate" => $acceptRate,
            "rejectRate" => $rejectRate,
            "topBuyer" => $topBuyer,
            "topPayoutBuyer" => $topPayoutBuyer,
            "totalRevenue" => $totalRevenue,
            "duplicateCount" => $duplicateLeadCount,
            "duplicateAttemptCount" => $duplicateAttemptCount,
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
