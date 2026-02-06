<?php

namespace App\Http\Controllers;

use App\Models\Attempt;
use App\Models\Buyer;
use App\Models\Lead;
use App\Models\LeadField;
use App\Services\BuyerRequestService;
use App\Services\BuyerResponseParser;
use App\Services\DuplicateChecker;
use App\Services\GoogleLogger;
use Illuminate\Http\Request;

class PublicFormController extends Controller
{
    public function show(string $token)
    {
        $buyer = Buyer::where("public_token", $token)
            ->where("public_enabled", true)
            ->firstOrFail();

        $fields = $this->buyerFields($buyer);
        $leadFields = LeadField::where("active", true)
            ->orderBy("key")
            ->get()
            ->keyBy("key");

        return view("forms.public", [
            "buyer" => $buyer,
            "fields" => $fields,
            "leadFields" => $leadFields,
        ]);
    }

    public function submit(string $token, Request $request, BuyerRequestService $service, BuyerResponseParser $parser, DuplicateChecker $duplicateChecker, GoogleLogger $googleLogger)
    {
        $buyer = Buyer::where("public_token", $token)
            ->where("public_enabled", true)
            ->firstOrFail();

        $leadData = $request->except(["_token"]);

        $lead = Lead::create([
            "product_id" => $buyer->default_product_id,
            "campaign_id" => $buyer->default_campaign_id,
            "publisher_id" => $buyer->default_publisher_id,
            "first_name" => $leadData["first_name"] ?? null,
            "last_name" => $leadData["last_name"] ?? null,
            "email" => $leadData["email"] ?? null,
            "phone" => $leadData["phone"] ?? null,
            "zip5" => $leadData["zip5"] ?? ($leadData["zip"] ?? null),
            "city" => $leadData["city"] ?? null,
            "state" => $leadData["state"] ?? null,
            "accident_state" => $leadData["accident_state"] ?? null,
            "ip_address" => $request->ip(),
            "source_url" => $request->headers->get("referer"),
            "cert_id" => $leadData["cert_id"] ?? null,
            "cert_url" => $leadData["cert_url"] ?? null,
            "lead_json" => $leadData,
        ]);

        $duplicate = $duplicateChecker->findDuplicateAttempt($buyer->id, $lead->phone);

        $result = $service->submit($buyer, $leadData);
        $pingParsed = $parser->parse(is_array($result["ping"] ?? null) ? $result["ping"] : null, $buyer->response_rules ?? []);
        $postParsed = $parser->parse(is_array($result["post"] ?? null) ? $result["post"] : null, $buyer->response_rules ?? []);
        $primary = $result["post"] ?? $result["upstream"] ?? $result["ping"] ?? null;
        $parsed = $parser->parse(is_array($primary) ? $primary : null, $buyer->response_rules ?? []);

        $payout = $postParsed["payout"] ?? $pingParsed["payout"] ?? $parsed["payout"] ?? null;
        if ($buyer->payout_type === "static" && $buyer->static_payout !== null) {
            $payout = $buyer->static_payout;
        }
        $bidAmount = $postParsed["bid_amount"] ?? $pingParsed["bid_amount"] ?? $parsed["bid_amount"] ?? null;
        $status = $parsed["status"] ?? "unknown";
        if ($buyer->type === "ping_post" && !($result["post"] ?? null)) {
            $status = "rejected";
        }
        $rejectReason = $this->extractRejectReason($parsed["body_json"] ?? null);
        if ($status === "accepted" && $rejectReason !== "") {
            $status = "rejected";
        }

        Attempt::create([
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
            "forwarding_number" => $postParsed["forwarding_number"] ?? $pingParsed["forwarding_number"] ?? $parsed["forwarding_number"] ?? null,
            "payout" => $payout,
            "bid_amount" => $bidAmount,
            "payload_json" => $leadData,
            "response_json" => $postParsed["body_json"] ?? $pingParsed["body_json"] ?? $parsed["body_json"] ?? null,
            "response_raw" => $postParsed["body_raw"] ?? $pingParsed["body_raw"] ?? $parsed["body_raw"] ?? null,
        ]);

        $googleLogger->log([
            "endpoint" => $buyer->code,
            "lead" => $leadData,
            "payload" => $result,
            "response" => $result,
        ]);

        return view("forms.result", [
            "buyer" => $buyer,
            "result" => $result,
            "parsed" => $parsed,
        ]);
    }

    private function buyerFields(Buyer $buyer): array
    {
        $fields = $buyer->fields()
            ->where("required", true)
            ->where("source_type", "lead")
            ->orderBy("field_name")
            ->get()
            ->pluck("source_key", "field_name")
            ->toArray();

        $required = array_values(array_unique(array_values($fields)));
        if (!empty($required)) {
            return $required;
        }

        $fallback = $buyer->fields()
            ->where("source_type", "lead")
            ->orderBy("field_name")
            ->get()
            ->pluck("source_key", "field_name")
            ->toArray();

        $allMapped = array_values(array_unique(array_values($fallback)));
        if (!empty($allMapped)) {
            return $allMapped;
        }

        return ["first_name", "last_name", "phone", "zip5"];
    }

    private function extractRejectReason(?array $data): string
    {
        if (!is_array($data)) return "";
        if (!empty($data["rejectReason"])) return (string) $data["rejectReason"];
        if (!empty($data["reject_reason"])) return (string) $data["reject_reason"];
        if (!empty($data["error"])) return (string) $data["error"];
        if (!empty($data["message"])) return (string) $data["message"];
        if (!empty($data["msg"])) return (string) $data["msg"];
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
        return "";
    }
}
