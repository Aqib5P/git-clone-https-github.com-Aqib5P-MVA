<?php

namespace App\Http\Controllers;

use App\Models\Attempt;
use App\Models\Buyer;
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
                sum(case when status = 'rejected' then 1 else 0 end) as rejected")
            ->whereBetween("created_at", [$startDate, $endDate])
            ->when($status, fn ($q) => $q->where("status", $status))
            ->when($buyer, fn ($q) => $q->where("endpoint", $buyer))
            ->groupBy("endpoint")
            ->orderBy("endpoint")
            ->get();

        $buyers = Buyer::orderBy("code")->get();

        return view("dashboard", [
            "attempts" => $attempts,
            "statusCounts" => $statusCounts,
            "buyerStats" => $buyerStats,
            "buyers" => $buyers,
            "filters" => [
                "start_date" => $startDate->toDateString(),
                "end_date" => $endDate->toDateString(),
                "status" => $status,
                "buyer" => $buyer,
            ],
        ]);
    }
}
