<?php

namespace App\Http\Controllers;

use App\Models\Attempt;
use App\Models\Buyer;
use App\Models\Lead;
use App\Models\LeadField;
use App\Models\RtbBid;
use App\Services\BuyerRequestService;
use App\Services\BuyerResponseParser;
use App\Services\DuplicateChecker;
use App\Services\GoogleLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RtbController extends Controller
{
    public function show()
    {
        $buyers = $this->rtbBuyers();
        [$requiredKeys, $leadFields] = $this->resolveLeadFields($buyers);

        return view("forms.rtb", [
            "buyers" => $buyers,
            "leadFields" => $leadFields,
            "requiredKeys" => $requiredKeys,
            "results" => [],
        ]);
    }

    public function submit(
        Request $request,
        BuyerRequestService $service,
        BuyerResponseParser $parser,
        DuplicateChecker $duplicateChecker,
        GoogleLogger $googleLogger
    ) {
        $buyers = $this->rtbBuyers();
        [$requiredKeys, $leadFields] = $this->resolveLeadFields($buyers);

        if ($buyers->isEmpty()) {
            return view("forms.rtb", [
                "buyers" => $buyers,
                "leadFields" => $leadFields,
                "requiredKeys" => $requiredKeys,
                "results" => [],
                "error" => "No active RTB buyers configured.",
            ]);
        }

        $leadData = $request->except(["_token"]);
        $lead = Lead::create([
            "first_name" => $leadData["first_name"] ?? null,
            "last_name" => $leadData["last_name"] ?? null,
            "email" => $leadData["email"] ?? null,
            "phone" => $leadData["phone"] ?? ($leadData["phone_number"] ?? null),
            "zip5" => $leadData["zip5"] ?? ($leadData["zip"] ?? ($leadData["zip_code"] ?? null)),
            "city" => $leadData["city"] ?? null,
            "state" => $leadData["state"] ?? null,
            "accident_state" => $leadData["accident_state"] ?? null,
            "ip_address" => $request->ip(),
            "source_url" => $request->headers->get("referer"),
            "cert_id" => $leadData["cert_id"] ?? ($leadData["trusted_form_cert_id"] ?? null),
            "cert_url" => $leadData["cert_url"] ?? ($leadData["trusted_form_cert_url"] ?? null),
            "lead_json" => $leadData,
        ]);

        $results = [];
        foreach ($buyers as $buyer) {
            $duplicate = $duplicateChecker->findDuplicateAttempt($buyer->id, $lead->phone);

            $result = $service->submit($buyer, $leadData);
            $primary = $result["post"] ?? $result["upstream"] ?? $result["ping"] ?? null;
            $parsed = $parser->parse(is_array($primary) ? $primary : null, $buyer->response_rules ?? []);

            $payout = $parsed["payout"] ?? null;
            if ($buyer->payout_type === "static" && $buyer->static_payout !== null) {
                $payout = $buyer->static_payout;
            }
            $bidAmount = $parsed["bid_amount"] ?? $payout;

            $status = $parsed["status"] ?? "unknown";
            if ($status === "unknown" && is_numeric($bidAmount) && $bidAmount > 0) {
                $status = "accepted";
            }

            $attempt = Attempt::create([
                "lead_id" => $lead->id,
                "buyer_id" => $buyer->id,
                "endpoint" => $buyer->code,
                "direction" => $buyer->type === "ping_post" ? "post" : "single",
                "status" => $status,
                "is_duplicate" => $duplicate !== null,
                "duplicate_of_id" => $duplicate?->id,
                "duplicate_window" => config("admin.duplicate_window_days") . "d",
                "http_status" => $parsed["http_status"] ?? null,
                "ping_id" => $parsed["ping_id"] ?? null,
                "forwarding_number" => $parsed["forwarding_number"] ?? null,
                "payout" => $payout,
                "bid_amount" => $bidAmount,
                "payload_json" => $leadData,
                "response_json" => $parsed["body_json"] ?? null,
                "response_raw" => $parsed["body_raw"] ?? null,
            ]);

            $bidJson = $parsed["body_json"] ?? null;
            $minDuration = $this->extractFirst($bidJson, ["callMinDuration", "min_duration", "minimumDuration"]);
            if (!$minDuration && is_array($bidJson) && isset($bidJson["bidTerms"]) && is_array($bidJson["bidTerms"])) {
                foreach ($bidJson["bidTerms"] as $term) {
                    if (is_array($term) && isset($term["callMinDuration"])) {
                        $minDuration = (string) $term["callMinDuration"];
                        break;
                    }
                }
            }
            $rtbBid = RtbBid::create([
                "attempt_id" => $attempt->id,
                "buyer_id" => $buyer->id,
                "bid_id" => $this->extractFirst($bidJson, ["bidId", "bid_id", "id", "requestId"]),
                "bid_amount" => $bidAmount,
                "phone_number" => $this->extractFirst($bidJson, ["phoneNumber", "phone_number"]),
                "sip_address" => $this->extractFirst($bidJson, ["sipAddress", "sip_address"]),
                "expires_at" => $this->deriveExpiry($bidJson),
                "bid_json" => $bidJson,
            ]);

            $googleLogger->log([
                "endpoint" => $buyer->code,
                "lead" => $leadData,
                "payload" => $result,
                "response" => $result,
                "rtb_bid" => $rtbBid->toArray(),
            ]);

            $results[] = [
                "buyer" => $buyer->code,
                "status" => $status,
                "bid" => $bidAmount ?? 0,
                "payout" => $payout,
                "forwarding_number" => $parsed["forwarding_number"] ?? null,
                "min_duration" => $minDuration,
                "expires" => $this->extractFirst($bidJson, ["expireInSeconds", "expires_in", "expiresInSeconds"]),
                "duplicate" => $duplicate !== null,
            ];
        }

        usort($results, fn ($a, $b) => ($b["bid"] ?? 0) <=> ($a["bid"] ?? 0));

        return view("forms.rtb", [
            "buyers" => $buyers,
            "leadFields" => $leadFields,
            "requiredKeys" => $requiredKeys,
            "results" => $results,
        ]);
    }

    private function rtbBuyers()
    {
        return Buyer::query()
            ->where("active", true)
            ->where("scope", "rtb")
            ->orderBy("code")
            ->get();
    }

    private function resolveLeadFields($buyers): array
    {
        $requiredKeys = $buyers
            ->flatMap(function ($buyer) {
                return $buyer->fields()
                    ->where("required", true)
                    ->where("source_type", "lead")
                    ->pluck("source_key");
            })
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (count($requiredKeys) === 0) {
            $requiredKeys = [
                "first_name",
                "last_name",
                "email",
                "phone",
                "zip5",
                "state",
                "attorney",
                "cert_id",
            ];
        }

        $leadFields = LeadField::where("active", true)
            ->whereIn("key", $requiredKeys)
            ->orderBy("key")
            ->get()
            ->keyBy("key");

        return [$requiredKeys, $leadFields];
    }

    private function extractFirst(?array $data, array $keys): ?string
    {
        if (!is_array($data)) return null;
        foreach ($keys as $key) {
            if (isset($data[$key]) && $data[$key] !== "") {
                return is_scalar($data[$key]) ? (string) $data[$key] : null;
            }
        }
        return null;
    }

    private function deriveExpiry(?array $data): ?Carbon
    {
        if (!is_array($data)) return null;
        foreach (["expireInSeconds", "expires_in", "expiresInSeconds"] as $key) {
            if (isset($data[$key]) && is_numeric($data[$key])) {
                return Carbon::now()->addSeconds((int) $data[$key]);
            }
        }
        if (isset($data["expires_at"])) {
            try {
                return Carbon::parse($data["expires_at"]);
            } catch (\Throwable $e) {
                return null;
            }
        }
        return null;
    }
}
