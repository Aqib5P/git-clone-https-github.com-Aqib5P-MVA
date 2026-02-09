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
        $scope = $request->query("scope");
        $acceptedStates = ["accepted"];
        $rejectedStates = ["rejected", "declined", "bid too low", "error", "failed"];
        $acceptedStatesLower = array_map("strtolower", $acceptedStates);
        $rejectedStatesLower = array_map("strtolower", $rejectedStates);
        $acceptedList = implode("','", $acceptedStatesLower);
        $rejectedList = implode("','", $rejectedStatesLower);
        $applyStatusFilter = function ($query) use ($status, $acceptedStatesLower, $rejectedStatesLower) {
            if (!$status) return;
            $column = DB::raw("lower(status)");
            if ($status === "accepted") {
                $query->whereIn($column, $acceptedStatesLower);
                return;
            }
            if ($status === "rejected") {
                $query->whereIn($column, $rejectedStatesLower);
                return;
            }
            if ($status === "unknown") {
                $query->whereNotIn($column, array_merge($acceptedStatesLower, $rejectedStatesLower));
                return;
            }
            $query->where($column, strtolower($status));
        };

        $startDate = $start ? Carbon::parse($start)->startOfDay() : Carbon::today()->startOfDay();
        $endDate = $end ? Carbon::parse($end)->endOfDay() : Carbon::today()->endOfDay();

        $baseQuery = Attempt::query()
            ->with(["lead", "buyer"])
            ->whereBetween("created_at", [$startDate, $endDate]);

        $applyStatusFilter($baseQuery);

        if ($buyer) {
            $baseQuery->where("endpoint", $buyer);
        }

        if ($scope) {
            $baseQuery->whereHas("buyer", function ($query) use ($scope) {
                $query->where("scope", $scope);
            });
        }

        $attempts = (clone $baseQuery)
            ->orderByDesc("created_at")
            ->limit(100)
            ->get();

        $declinedCount = (clone $baseQuery)
            ->whereRaw("lower(status) = 'declined'")
            ->count();
        $bidTooLowCount = (clone $baseQuery)
            ->whereRaw("lower(status) = 'bid too low'")
            ->count();

        $leadStatusSub = Attempt::query()
            ->selectRaw("lead_id,
                max(case when lower(status) in ('{$acceptedList}') then 1 else 0 end) as has_accepted,
                max(case when lower(status) in ('{$rejectedList}') then 1 else 0 end) as has_rejected")
            ->whereBetween("created_at", [$startDate, $endDate])
            ->when($buyer, fn ($q) => $q->where("endpoint", $buyer))
            ->when($scope, function ($q) use ($scope) {
                $q->whereHas("buyer", function ($buyerQuery) use ($scope) {
                    $buyerQuery->where("scope", $scope);
                });
            })
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

        $buyerStatsQuery = Attempt::query()
            ->selectRaw("endpoint, count(*) as total,
                sum(case when lower(status) in ('{$acceptedList}') then 1 else 0 end) as accepted,
                sum(case when lower(status) in ('{$rejectedList}') then 1 else 0 end) as rejected,
                sum(payout) as total_payout,
                max(payout) as max_payout")
            ->whereBetween("created_at", [$startDate, $endDate])
            ->when($buyer, fn ($q) => $q->where("endpoint", $buyer))
            ->when($scope, function ($q) use ($scope) {
                $q->whereHas("buyer", function ($buyerQuery) use ($scope) {
                    $buyerQuery->where("scope", $scope);
                });
            })
            ->groupBy("endpoint")
            ->orderBy("endpoint");
        $applyStatusFilter($buyerStatsQuery);
        $buyerStats = $buyerStatsQuery->get();

        $topBuyer = $buyerStats->sortByDesc("accepted")->first();
        $topPayoutBuyer = $buyerStats->sortByDesc("total_payout")->first();
        $acceptRate = $total > 0 ? round(($accepted / $total) * 100, 1) : 0;
        $rejectRate = $total > 0 ? round(($rejected / $total) * 100, 1) : 0;
        $totalRevenueQuery = Attempt::query()
            ->whereBetween("created_at", [$startDate, $endDate])
            ->when($buyer, fn ($q) => $q->where("endpoint", $buyer))
            ->when($scope, function ($q) use ($scope) {
                $q->whereHas("buyer", function ($buyerQuery) use ($scope) {
                    $buyerQuery->where("scope", $scope);
                });
            });
        $applyStatusFilter($totalRevenueQuery);
        $totalRevenue = $totalRevenueQuery->sum("payout");
        $duplicateAttemptQuery = Attempt::query()
            ->whereBetween("created_at", [$startDate, $endDate])
            ->when($buyer, fn ($q) => $q->where("endpoint", $buyer))
            ->when($scope, function ($q) use ($scope) {
                $q->whereHas("buyer", function ($buyerQuery) use ($scope) {
                    $buyerQuery->where("scope", $scope);
                });
            })
            ->where("is_duplicate", true);
        $applyStatusFilter($duplicateAttemptQuery);
        $duplicateAttemptCount = $duplicateAttemptQuery->count();
        $duplicateLeadCount = Lead::query()
            ->whereBetween("created_at", [$startDate, $endDate])
            ->when($buyer, function ($query) use ($buyer) {
                $query->whereHas("attempts", function ($attemptQuery) use ($buyer) {
                    $attemptQuery->where("endpoint", $buyer);
                });
            })
            ->when($scope, function ($query) use ($scope) {
                $query->whereHas("attempts", function ($attemptQuery) use ($scope) {
                    $attemptQuery->whereHas("buyer", function ($buyerQuery) use ($scope) {
                        $buyerQuery->where("scope", $scope);
                    });
                });
            })
            ->whereNotNull("phone")
            ->selectRaw("phone, count(*) as cnt")
            ->groupBy("phone")
            ->havingRaw("count(*) > 1")
            ->get()
            ->sum(fn ($row) => (int) $row->cnt - 1);
        $duplicateRate = $total > 0 ? round(($duplicateLeadCount / $total) * 100, 1) : 0;

        $buyers = Buyer::orderBy("code")
            ->when($scope, fn ($q) => $q->where("scope", $scope))
            ->get();
        $scopeOptions = Buyer::query()
            ->select("scope")
            ->distinct()
            ->orderBy("scope")
            ->pluck("scope")
            ->filter()
            ->values();

        $productStats = Lead::query()
            ->with("product")
            ->selectRaw("product_id, count(*) as total")
            ->whereBetween("created_at", [$startDate, $endDate])
            ->when($scope, function ($query) use ($scope) {
                $query->whereHas("attempts", function ($attemptQuery) use ($scope) {
                    $attemptQuery->whereHas("buyer", function ($buyerQuery) use ($scope) {
                        $buyerQuery->where("scope", $scope);
                    });
                });
            })
            ->groupBy("product_id")
            ->orderByDesc("total")
            ->get();

        $campaignStats = Lead::query()
            ->with("campaign")
            ->selectRaw("campaign_id, count(*) as total")
            ->whereBetween("created_at", [$startDate, $endDate])
            ->when($scope, function ($query) use ($scope) {
                $query->whereHas("attempts", function ($attemptQuery) use ($scope) {
                    $attemptQuery->whereHas("buyer", function ($buyerQuery) use ($scope) {
                        $buyerQuery->where("scope", $scope);
                    });
                });
            })
            ->groupBy("campaign_id")
            ->orderByDesc("total")
            ->get();

        $publisherStats = Lead::query()
            ->with("publisher")
            ->selectRaw("publisher_id, count(*) as total")
            ->whereBetween("created_at", [$startDate, $endDate])
            ->when($scope, function ($query) use ($scope) {
                $query->whereHas("attempts", function ($attemptQuery) use ($scope) {
                    $attemptQuery->whereHas("buyer", function ($buyerQuery) use ($scope) {
                        $buyerQuery->where("scope", $scope);
                    });
                });
            })
            ->groupBy("publisher_id")
            ->orderByDesc("total")
            ->get();

        return view("dashboard", [
            "attempts" => $attempts,
            "buyerStats" => $buyerStats,
            "buyers" => $buyers,
            "scopeOptions" => $scopeOptions,
            "accepted" => $accepted,
            "rejected" => $rejected,
            "unknown" => $unknown,
            "total" => $total,
            "acceptRate" => $acceptRate,
            "rejectRate" => $rejectRate,
            "declinedCount" => $declinedCount,
            "bidTooLowCount" => $bidTooLowCount,
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
                "scope" => $scope,
            ],
        ]);
    }
}
