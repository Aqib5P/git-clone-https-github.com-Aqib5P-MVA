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
        $minBid = 20;
        foreach ($buyers as $buyer) {
            $duplicate = $duplicateChecker->findDuplicateAttempt($buyer->id, $lead->phone);

            $result = $service->submit($buyer, $leadData);
            $pingParsed = $parser->parse(is_array($result["ping"] ?? null) ? $result["ping"] : null, $buyer->response_rules ?? []);
            $postParsed = $parser->parse(is_array($result["post"] ?? null) ? $result["post"] : null, $buyer->response_rules ?? []);
            $primary = $result["post"] ?? $result["upstream"] ?? $result["ping"] ?? null;
            $parsed = $parser->parse(is_array($primary) ? $primary : null, $buyer->response_rules ?? []);

            $bodyData = $postParsed["body_json"] ?? $pingParsed["body_json"] ?? $parsed["body_json"] ?? null;
            $payout = $postParsed["payout"] ?? $pingParsed["payout"] ?? $parsed["payout"] ?? null;
            if ($buyer->payout_type === "static" && $buyer->static_payout !== null) {
                $payout = $buyer->static_payout;
            }
            if ($payout === null && is_array($bodyData)) {
                $payout = $this->extractNumberFromBody($bodyData, ["payout", "price", "offer_conversion_payout", "bidAmount", "bidPrice"]);
            }

            $bidAmount = $postParsed["bid_amount"] ?? $pingParsed["bid_amount"] ?? $parsed["bid_amount"];
            if ($bidAmount === null && is_array($bodyData)) {
                $bidAmount = $this->extractNumberFromBody($bodyData, ["bidAmount", "bid_amount", "bidPrice"]);
            }
            $forwardingNumber = $postParsed["forwarding_number"] ?? $pingParsed["forwarding_number"] ?? $parsed["forwarding_number"] ?? null;
            $rejectReason = $this->extractRejectReason($bodyData);

            $status = $parsed["status"] ?? "unknown";
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

            $attempt = Attempt::create([
                "lead_id" => $lead->id,
                "buyer_id" => $buyer->id,
                "endpoint" => $buyer->code,
                "direction" => $buyer->type === "ping_post" ? "post" : "single",
                "status" => $status,
                "is_duplicate" => $duplicate !== null,
                "duplicate_of_id" => $duplicate?->id,
                "duplicate_window" => config("admin.duplicate_window_days") . "d",
                "http_status" => $postParsed["http_status"] ?? $pingParsed["http_status"] ?? $parsed["http_status"] ?? null,
                "ping_id" => $pingParsed["ping_id"] ?? $parsed["ping_id"] ?? null,
                "forwarding_number" => $forwardingNumber,
                "payout" => $payout,
                "bid_amount" => $bidAmount,
                "payload_json" => $leadData,
                "response_json" => $postParsed["body_json"] ?? $pingParsed["body_json"] ?? $parsed["body_json"] ?? null,
                "response_raw" => $postParsed["body_raw"] ?? $pingParsed["body_raw"] ?? $parsed["body_raw"] ?? null,
            ]);

            $bidJson = $postParsed["body_json"] ?? $pingParsed["body_json"] ?? $parsed["body_json"] ?? null;
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
                "sort_bid" => $numericBid ?? 0,
                "forwarding_number" => $forwardingNumber,
                "min_duration" => $minDuration,
                "expires" => $this->extractFirst($bidJson, ["expireInSeconds", "expires_in", "expiresInSeconds"]),
                "duplicate" => $duplicate !== null,
            ];
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

    private function extractRejectReason(?array $data): string
    {
        if (!is_array($data)) return "";
        if (!empty($data["rejectReason"])) return (string) $data["rejectReason"];
        if (!empty($data["reject_reason"])) return (string) $data["reject_reason"];
        if (!empty($data["error"])) return (string) $data["error"];
        if (!empty($data["errors"])) {
            if (is_array($data["errors"])) {
                $flat = [];
                foreach ($data["errors"] as $err) {
                    $flat[] = is_array($err) ? implode(", ", $err) : (string) $err;
                }
                return implode(" | ", $flat);
            }
            return (string) $data["errors"];
        }
        if (!empty($data["message"]) && $this->isRejectText((string) $data["message"])) return (string) $data["message"];
        if (!empty($data["msg"]) && $this->isRejectText((string) $data["msg"])) return (string) $data["msg"];
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
