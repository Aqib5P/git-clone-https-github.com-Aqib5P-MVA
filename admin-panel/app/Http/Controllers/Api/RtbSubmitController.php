<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\Buyer;
use App\Models\Lead;
use App\Models\RtbBid;
use App\Services\BuyerRequestService;
use App\Services\BuyerResponseParser;
use App\Services\DuplicateChecker;
use App\Services\GoogleLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RtbSubmitController extends Controller
{
    public function submit(
        Request $request,
        BuyerRequestService $service,
        BuyerResponseParser $parser,
        DuplicateChecker $duplicateChecker,
        GoogleLogger $googleLogger
    ) {
        $data = $request->json()->all();
        $leadData = $data["data"] ?? $data["lead"] ?? $data;
        if (!is_array($leadData)) $leadData = [];

        $leadData = $this->normalizeLeadData($leadData);

        $buyers = Buyer::query()
            ->where("active", true)
            ->where("scope", "rtb")
            ->orderBy("code")
            ->get();

        if ($buyers->isEmpty()) {
            return $this->cors(response()->json([
                "error" => "No RTB buyers configured.",
            ], 422));
        }

        $lead = Lead::create([
            "product_id" => $buyers->first()?->default_product_id,
            "campaign_id" => $buyers->first()?->default_campaign_id,
            "publisher_id" => $buyers->first()?->default_publisher_id,
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
            RtbBid::create([
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
            ]);

            $minDuration = $this->extractFirst($bidJson, ["callMinDuration", "min_duration", "minimumDuration"]);
            if (!$minDuration && is_array($bidJson) && isset($bidJson["bidTerms"]) && is_array($bidJson["bidTerms"])) {
                foreach ($bidJson["bidTerms"] as $term) {
                    if (is_array($term) && isset($term["callMinDuration"])) {
                        $minDuration = (string) $term["callMinDuration"];
                        break;
                    }
                }
            }

            $results[] = [
                "buyer" => $buyer->code,
                "status" => $status,
                "bid" => $bidAmount ?? 0,
                "payout" => $payout,
                "forwarding_number" => $parsed["forwarding_number"] ?? null,
                "min_duration" => $minDuration,
                "expires" => $this->extractFirst($bidJson, ["expireInSeconds", "expires_in", "expiresInSeconds"]),
                "duplicate" => $duplicate !== null,
                "response_raw" => $parsed["body_raw"] ?? null,
                "response_json" => $parsed["body_json"] ?? null,
            ];
        }

        usort($results, fn ($a, $b) => ($b["bid"] ?? 0) <=> ($a["bid"] ?? 0));

        $response = response()->json([
            "lead_id" => $lead->id,
            "results" => $results,
        ]);

        return $this->cors($response);
    }

    private function normalizeLeadData(array $leadData): array
    {
        if (!isset($leadData["phone"]) && isset($leadData["phone_number"])) {
            $leadData["phone"] = $leadData["phone_number"];
        }
        if (!isset($leadData["zip5"])) {
            $leadData["zip5"] = $leadData["zip_code"] ?? ($leadData["zip"] ?? null);
        }
        if (!isset($leadData["cert_id"])) {
            $leadData["cert_id"] = $leadData["trusted_form_cert_id"] ?? null;
        }
        if (!isset($leadData["cert_url"]) && isset($leadData["trusted_form_cert_url"])) {
            $leadData["cert_url"] = $leadData["trusted_form_cert_url"];
        }
        if (!isset($leadData["attorney"]) && isset($leadData["have_attorney"])) {
            $value = strtolower((string) $leadData["have_attorney"]);
            $leadData["attorney"] = in_array($value, ["yes", "y", "1", "true"], true) ? "Yes" : "No";
        }

        return $leadData;
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

    private function cors($response)
    {
        return $response->header("Access-Control-Allow-Origin", "*")
            ->header("Access-Control-Allow-Headers", "Content-Type")
            ->header("Access-Control-Allow-Methods", "POST, OPTIONS");
    }
}
