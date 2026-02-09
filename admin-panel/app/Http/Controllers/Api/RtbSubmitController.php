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
use App\Http\Controllers\Concerns\RecordSourceHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RtbSubmitController extends Controller
{
    use RecordSourceHelper;

    public function submit(
        Request $request,
        BuyerRequestService $service,
        BuyerResponseParser $parser,
        DuplicateChecker $duplicateChecker,
        GoogleLogger $googleLogger
    ) {
        try {
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
        $submittedAt = Carbon::now()->toIso8601String();
        $minBid = 20;
        foreach ($buyers as $buyer) {
            $duplicate = $duplicateChecker->findDuplicateAttempt($buyer->id, $lead->phone);

            try {
                $result = $service->submit($buyer, $leadData);
                $pingParsed = $parser->parse(is_array($result["ping"] ?? null) ? $result["ping"] : null, $this->rulesFor($buyer, "ping"));
                $postParsed = $parser->parse(is_array($result["post"] ?? null) ? $result["post"] : null, $this->rulesFor($buyer, "post"));
                $primary = $result["post"] ?? $result["upstream"] ?? $result["ping"] ?? null;
                $parsed = $parser->parse(is_array($primary) ? $primary : null, $this->rulesFor($buyer, "single"));

                $recordSources = $this->recordSources($buyer);
                $responseOrder = ["post", "ping", "single"];
                if (!array_key_exists("response", $recordSources)) {
                    $payoutPref = $recordSources["payout"] ?? null;
                    $bidPref = $recordSources["bid_amount"] ?? null;
                    if ($payoutPref === "ping" || $bidPref === "ping") {
                        $responseOrder = ["ping", "post", "single"];
                    }
                }
                $responseJson = $this->pickRecordValue($recordSources, "response", [
                    "post" => $postParsed["body_json"] ?? null,
                    "ping" => $pingParsed["body_json"] ?? null,
                    "single" => $parsed["body_json"] ?? null,
                ], $responseOrder);
                $responseRaw = $this->pickRecordValue($recordSources, "response", [
                    "post" => $postParsed["body_raw"] ?? null,
                    "ping" => $pingParsed["body_raw"] ?? null,
                    "single" => $parsed["body_raw"] ?? null,
                ], $responseOrder, false, true);
                $bodyData = is_array($responseJson) ? $responseJson : null;
                $payout = $this->pickRecordValue($recordSources, "payout", [
                    "post" => $postParsed["payout"] ?? null,
                    "ping" => $pingParsed["payout"] ?? null,
                    "single" => $parsed["payout"] ?? null,
                ], ["post", "ping", "single"]);
                if ($payout === null && is_array($bodyData)) {
                    $payout = $this->extractNumberFromBody($bodyData, ["payout", "price", "offer_conversion_payout", "bidAmount", "bidPrice"]);
                }

                $bidAmount = $this->pickRecordValue($recordSources, "bid_amount", [
                    "post" => $postParsed["bid_amount"] ?? null,
                    "ping" => $pingParsed["bid_amount"] ?? null,
                    "single" => $parsed["bid_amount"] ?? null,
                ], ["post", "ping", "single"]);
                if ($bidAmount === null && is_array($bodyData)) {
                    $bidAmount = $this->extractNumberFromBody($bodyData, ["bidAmount", "bid_amount", "bidPrice"]);
                }
                $forwardingNumber = $this->pickRecordValue($recordSources, "forwarding_number", [
                    "post" => $postParsed["forwarding_number"] ?? null,
                    "ping" => $pingParsed["forwarding_number"] ?? null,
                    "single" => $parsed["forwarding_number"] ?? null,
                ], ["post", "ping", "single"]);
                $rejectReason = $this->extractRejectReason($bodyData);

                $status = $parsed["status"] ?? "unknown";
                if (array_key_exists("status", $recordSources)) {
                    $pickedStatus = $this->pickRecordValue($recordSources, "status", [
                        "post" => $postParsed["status"] ?? null,
                        "ping" => $pingParsed["status"] ?? null,
                        "single" => $parsed["status"] ?? null,
                    ], ["post", "ping", "single"]);
                    if ($pickedStatus !== null) $status = $pickedStatus;
                }
                $numericBid = is_numeric($bidAmount) ? (float) $bidAmount : null;
                $numericPayout = is_numeric($payout) ? (float) $payout : null;
                if ($numericBid === null && $numericPayout !== null) {
                    $numericBid = $numericPayout;
                    $bidAmount = $numericPayout;
                }
                $hasForwarding = is_string($forwardingNumber) && trim($forwardingNumber) !== "";

                if (($numericBid !== null && $numericBid > 0 && $numericBid < $minBid) || ($numericPayout !== null && $numericPayout > 0 && $numericPayout < $minBid)) {
                    $status = "Bid too low";
                } elseif ($rejectReason !== "") {
                    $status = "rejected";
                } elseif (($numericBid !== null && $numericBid >= $minBid) || ($numericPayout !== null && $numericPayout >= $minBid) || $hasForwarding) {
                    $status = "accepted";
                } elseif ($buyer->type === "ping_post" && !($result["post"] ?? null)) {
                    $status = "rejected";
                } elseif (!in_array($status, ["accepted", "rejected"], true)) {
                    $status = "rejected";
                }
                if ($status === "accepted" && $payout === null && $buyer->payout_type === "static" && $buyer->static_payout !== null) {
                    $payout = $buyer->static_payout;
                    if ($bidAmount === null) {
                        $bidAmount = $payout;
                    }
                    if (is_numeric($payout)) {
                        $numericPayout = (float) $payout;
                        if ($numericBid === null) $numericBid = $numericPayout;
                    }
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
                    "http_status" => $this->pickRecordValue($recordSources, "http_status", [
                        "post" => $postParsed["http_status"] ?? null,
                        "ping" => $pingParsed["http_status"] ?? null,
                        "single" => $parsed["http_status"] ?? null,
                    ], ["post", "ping", "single"]),
                    "ping_id" => $this->pickRecordValue($recordSources, "ping_id", [
                        "ping" => $pingParsed["ping_id"] ?? null,
                        "post" => $postParsed["ping_id"] ?? null,
                        "single" => $parsed["ping_id"] ?? null,
                    ], ["ping", "post", "single"]),
                    "forwarding_number" => $forwardingNumber,
                    "payout" => $payout,
                    "bid_amount" => $bidAmount,
                    "payload_json" => $leadData,
                    "response_json" => $responseJson,
                    "response_raw" => $responseRaw,
                ]);

                $bidJson = $responseJson ?? $bodyData;
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
                if (!$minDuration && is_array($bodyData)) {
                    $minDuration = $this->extractDurationFromBody($bodyData);
                }

                $results[] = [
                    "buyer" => $buyer->code,
                    "status" => $status,
                    "sort_bid" => $numericBid ?? 0,
                    "forwarding_number" => $forwardingNumber,
                    "min_duration" => $minDuration,
                    "expires" => $this->extractFirst($bidJson, ["expireInSeconds", "expires_in", "expiresInSeconds"]),
                    "duplicate" => $duplicate !== null,
                    "timestamp" => $submittedAt,
                ];
            } catch (\Throwable $e) {
                Log::error("RTB buyer failed", [
                    "buyer" => $buyer->code,
                    "error" => $e->getMessage(),
                ]);
                try {
                    Attempt::create([
                        "lead_id" => $lead->id,
                        "buyer_id" => $buyer->id,
                        "endpoint" => $buyer->code,
                        "direction" => $buyer->type === "ping_post" ? "post" : "single",
                        "status" => "error",
                        "is_duplicate" => $duplicate !== null,
                        "duplicate_of_id" => $duplicate?->id,
                        "duplicate_window" => config("admin.duplicate_window_days") . "d",
                        "payload_json" => $leadData,
                        "response_raw" => $e->getMessage(),
                    ]);
                } catch (\Throwable $inner) {
                    Log::error("RTB attempt log failed", ["error" => $inner->getMessage()]);
                }
                $results[] = [
                    "buyer" => $buyer->code,
                    "status" => "error",
                    "sort_bid" => 0,
                    "forwarding_number" => null,
                    "min_duration" => null,
                    "expires" => null,
                    "duplicate" => $duplicate !== null,
                    "timestamp" => $submittedAt,
                ];
            }
        }

        usort($results, fn ($a, $b) => ($b["sort_bid"] ?? 0) <=> ($a["sort_bid"] ?? 0));
        $ranked = 0;
        foreach ($results as &$row) {
            if (strtolower((string) $row["status"]) === "accepted") {
                $row["rank_label"] = match ($ranked) {
                    0 => "Highest Payout",
                    1 => "2nd Highest",
                    2 => "3rd Highest",
                    default => "",
                };
                $ranked++;
            } else {
                $row["rank_label"] = "";
            }
            unset($row["sort_bid"]);
        }
        unset($row);

        $response = response()->json([
            "lead_id" => $lead->id,
            "submitted_at" => $submittedAt,
            "results" => $results,
        ]);

        return $this->cors($response);
        } catch (\Throwable $e) {
            Log::error("RTB submit failed", [
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);
            return $this->cors(response()->json([
                "error" => "RTB submit failed",
                "message" => $e->getMessage(),
            ], 500));
        }
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

    private function extractDurationFromBody(array $data): ?string
    {
        foreach (["duration", "current_conversion_duration", "min_duration", "minimumDuration"] as $key) {
            if (isset($data[$key]) && $data[$key] !== "") {
                return (string) $data[$key];
            }
        }
        if (isset($data["buyers"]) && is_array($data["buyers"]) && isset($data["buyers"][0]) && is_array($data["buyers"][0])) {
            $buyer = $data["buyers"][0];
            foreach (["current_conversion_duration", "duration", "min_duration"] as $key) {
                if (isset($buyer[$key]) && $buyer[$key] !== "") {
                    return (string) $buyer[$key];
                }
            }
        }
        return null;
    }

    private function extractNumberFromBody(array $data, array $keys): ?float
    {
        foreach ($keys as $key) {
            if (isset($data[$key]) && is_numeric($data[$key])) {
                return (float) $data[$key];
            }
        }
        if (isset($data["response"]) && is_array($data["response"])) {
            foreach ($keys as $key) {
                if (isset($data["response"][$key]) && is_numeric($data["response"][$key])) {
                    return (float) $data["response"][$key];
                }
            }
        }
        if (isset($data["buyers"]) && is_array($data["buyers"]) && isset($data["buyers"][0]) && is_array($data["buyers"][0])) {
            foreach ($keys as $key) {
                if (isset($data["buyers"][0][$key]) && is_numeric($data["buyers"][0][$key])) {
                    return (float) $data["buyers"][0][$key];
                }
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

    private function rulesFor(Buyer $buyer, string $direction): array
    {
        $rules = $buyer->response_rules ?? [];
        if (!is_array($rules)) return [];
        if ($direction === "single" && isset($rules["single"])) return $rules["single"];
        if (isset($rules[$direction])) return $rules[$direction];
        return $rules;
    }

    private function extractRejectReason(?array $data): string
    {
        if (!is_array($data)) return "";
        if (!empty($data["rejectReason"])) return (string) $data["rejectReason"];
        if (!empty($data["reject_reason"])) return (string) $data["reject_reason"];
        if (!empty($data["error"])) return $this->stringifyValue($data["error"]);
        if (!empty($data["errors"])) {
            if (is_array($data["errors"])) {
                $flat = [];
                foreach ($data["errors"] as $err) {
                    $flat[] = $this->stringifyValue($err);
                }
                return implode(" | ", $flat);
            }
            return $this->stringifyValue($data["errors"]);
        }
        if (!empty($data["message"])) {
            $message = $this->stringifyValue($data["message"]);
            if ($this->isRejectText($message)) return $message;
        }
        if (!empty($data["msg"])) {
            $message = $this->stringifyValue($data["msg"]);
            if ($this->isRejectText($message)) return $message;
        }
        return "";
    }

    private function stringifyValue($value): string
    {
        if (is_string($value)) return $value;
        if (is_numeric($value)) return (string) $value;
        if (is_array($value)) {
            $flat = [];
            foreach ($value as $item) {
                $flat[] = $this->stringifyValue($item);
            }
            return implode(", ", array_filter($flat, fn ($v) => $v !== ""));
        }
        if (is_object($value)) {
            $json = json_encode($value);
            return $json !== false ? $json : "";
        }
        return "";
    }

    private function isRejectText(string $text): bool
    {
        $t = strtolower($text);
        return str_contains($t, "reject")
            || str_contains($t, "declin")
            || str_contains($t, "fail")
            || str_contains($t, "error")
            || str_contains($t, "invalid")
            || str_contains($t, "no match")
            || str_contains($t, "no_matching")
            || str_contains($t, "no matching");
    }
}
