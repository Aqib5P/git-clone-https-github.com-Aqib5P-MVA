<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\Buyer;
use App\Models\Lead;
use App\Services\BuyerRequestService;
use App\Services\BuyerResponseParser;
use Illuminate\Http\Request;

class BuyerSubmitController extends Controller
{
    public function submit(Request $request, BuyerRequestService $service, BuyerResponseParser $parser)
    {
        $data = $request->json()->all();
        $endpoint = $data["endpoint"] ?? "";
        $leadData = $data["data"] ?? $data["lead"] ?? [];

        $buyer = Buyer::where("code", strtoupper($endpoint))
            ->where("active", true)
            ->first();

        if (!$buyer) {
            return $this->cors(response()->json(["error" => "Unknown buyer"], 404));
        }

        if (!in_array($buyer->scope, ["unified", "all"], true)) {
            return $this->cors(response()->json(["error" => "Buyer not enabled for unified scope"], 403));
        }

        $lead = Lead::create([
            "first_name" => $leadData["first_name"] ?? null,
            "last_name" => $leadData["last_name"] ?? null,
            "email" => $leadData["email"] ?? null,
            "phone" => $leadData["phone"] ?? null,
            "zip5" => $leadData["zip5"] ?? ($leadData["zip"] ?? null),
            "city" => $leadData["city"] ?? null,
            "state" => $leadData["state"] ?? null,
            "accident_state" => $leadData["accident_state"] ?? null,
            "ip_address" => $leadData["ip_address"] ?? $request->ip(),
            "source_url" => $leadData["source_url"] ?? $request->headers->get("referer"),
            "cert_id" => $leadData["cert_id"] ?? null,
            "cert_url" => $leadData["cert_url"] ?? null,
            "lead_json" => $leadData,
        ]);

        $result = $service->submit($buyer, $leadData);
        $primary = $result["post"] ?? $result["upstream"] ?? $result["ping"] ?? null;
        $parsed = $parser->parse(is_array($primary) ? $primary : null);

        Attempt::create([
            "lead_id" => $lead->id,
            "buyer_id" => $buyer->id,
            "endpoint" => $buyer->code,
            "direction" => $buyer->type === "ping_post" ? "post" : "single",
            "status" => $parsed["status"] ?? "unknown",
            "http_status" => $parsed["http_status"] ?? null,
            "ping_id" => $parsed["ping_id"] ?? null,
            "forwarding_number" => $parsed["forwarding_number"] ?? null,
            "payout" => $parsed["payout"] ?? null,
            "bid_amount" => $parsed["bid_amount"] ?? null,
            "payload_json" => $leadData,
            "response_json" => $parsed["body_json"] ?? null,
            "response_raw" => $parsed["body_raw"] ?? null,
        ]);

        $response = [
            "endpoint" => $buyer->code,
            "upstream" => $result["upstream"] ?? null,
            "ping" => $result["ping"] ?? null,
            "post" => $result["post"] ?? null,
        ];

        return $this->cors(response()->json($response));
    }

    private function cors($response)
    {
        return $response->header("Access-Control-Allow-Origin", "*")
            ->header("Access-Control-Allow-Headers", "Content-Type")
            ->header("Access-Control-Allow-Methods", "POST, OPTIONS");
    }
}
