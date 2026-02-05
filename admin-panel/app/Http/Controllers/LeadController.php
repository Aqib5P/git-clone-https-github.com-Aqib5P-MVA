<?php

namespace App\Http\Controllers;

use App\Models\Attempt;
use App\Models\Buyer;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $start = $request->input("start_date") ?: Carbon::now()->subDays(7)->toDateString();
        $end = $request->input("end_date") ?: Carbon::now()->toDateString();
        $buyer = $request->input("buyer");
        $status = $request->input("status");
        $search = trim((string) $request->input("search", ""));

        $startDate = Carbon::parse($start)->startOfDay();
        $endDate = Carbon::parse($end)->endOfDay();

        $buyers = Buyer::orderBy("code")->get(["id", "code", "name"]);

        $attempts = Attempt::query()
            ->with(["lead", "buyer"])
            ->whereBetween("created_at", [$startDate, $endDate])
            ->when($buyer, function ($query) use ($buyer) {
                $query->where("buyer_id", $buyer);
            })
            ->when($status, function ($query) use ($status) {
                $query->where("status", $status);
            })
            ->when($search !== "", function ($query) use ($search) {
                $query->whereHas("lead", function ($leadQuery) use ($search) {
                    $leadQuery->where("phone", "like", "%{$search}%")
                        ->orWhere("email", "like", "%{$search}%")
                        ->orWhere("first_name", "like", "%{$search}%")
                        ->orWhere("last_name", "like", "%{$search}%");
                });
            })
            ->orderByDesc("created_at")
            ->paginate(50)
            ->withQueryString();

        return view("leads.index", [
            "buyers" => $buyers,
            "attempts" => $attempts,
            "filters" => [
                "start_date" => $start,
                "end_date" => $end,
                "buyer" => $buyer,
                "status" => $status,
                "search" => $search,
            ],
        ]);
    }
}
