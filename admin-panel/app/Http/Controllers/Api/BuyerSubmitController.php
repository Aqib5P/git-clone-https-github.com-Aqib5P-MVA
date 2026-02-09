<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\Buyer;
use App\Models\Lead;
use App\Services\BuyerRequestService;
use App\Services\BuyerResponseParser;
use App\Services\DuplicateChecker;
use App\Services\GoogleLogger;
use App\Http\Controllers\Concerns\RecordSourceHelper;
use Illuminate\Http\Request;

class BuyerSubmitController extends Controller
{
    use RecordSourceHelper;

    public function submit(Request $request, BuyerRequestService $service, BuyerResponseParser $parser, DuplicateChecker $duplicateChecker, GoogleLogger $googleLogger)
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
            "ip_address" => $leadData["ip_address"] ?? $request->ip(),
            "source_url" => $leadData["source_url"] ?? $request->headers->get("referer"),
            "cert_id" => $leadData["cert_id"] ?? null,
            "cert_url" => $leadData["cert_url"] ?? null,
            "lead_json" => $leadData,
        ]);

        $duplicate = $duplicateChecker->findDuplicateAttempt($buyer->id, $lead->phone);

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
        $payout = $this->pickRecordValue($recordSources, "payout", [
            "post" => $postParsed["payout"] ?? null,
            "ping" => $pingParsed["payout"] ?? null,
            "single" => $parsed["payout"] ?? null,
        ], ["post", "ping", "single"]);
        $bidAmount = $this->pickRecordValue($recordSources, "bid_amount", [
            "post" => $postParsed["bid_amount"] ?? null,
            "ping" => $pingParsed["bid_amount"] ?? null,
            "single" => $parsed["bid_amount"] ?? null,
        ], ["post", "ping", "single"]);
        $status = $parsed["status"] ?? "unknown";
        if (array_key_exists("status", $recordSources)) {
            $pickedStatus = $this->pickRecordValue($recordSources, "status", [
                "post" => $postParsed["status"] ?? null,
                "ping" => $pingParsed["status"] ?? null,
                "single" => $parsed["status"] ?? null,
            ], ["post", "ping", "single"]);
            if ($pickedStatus !== null) $status = $pickedStatus;
        }
        if ($buyer->type === "ping_post" && !($result["post"] ?? null)) {
            $status = "rejected";
        }
        $rejectReason = $this->extractRejectReason($parsed["body_json"] ?? null);
        if ($status === "accepted" && $rejectReason !== "") {
            $status = "rejected";
        }
        if ($status === "accepted" && $payout === null && $buyer->payout_type === "static" && $buyer->static_payout !== null) {
            $payout = $buyer->static_payout;
        }

        $httpStatus = $this->pickRecordValue($recordSources, "http_status", [
            "post" => $postParsed["http_status"] ?? null,
            "ping" => $pingParsed["http_status"] ?? null,
            "single" => $parsed["http_status"] ?? null,
        ], ["post", "ping", "single"]);
        $pingId = $this->pickRecordValue($recordSources, "ping_id", [
            "ping" => $pingParsed["ping_id"] ?? null,
            "post" => $postParsed["ping_id"] ?? null,
            "single" => $parsed["ping_id"] ?? null,
        ], ["ping", "post", "single"]);
        $forwardingNumber = $this->pickRecordValue($recordSources, "forwarding_number", [
            "post" => $postParsed["forwarding_number"] ?? null,
            "ping" => $pingParsed["forwarding_number"] ?? null,
            "single" => $parsed["forwarding_number"] ?? null,
        ], ["post", "ping", "single"]);
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

        Attempt::create([
            "lead_id" => $lead->id,
            "buyer_id" => $buyer->id,
            "endpoint" => $buyer->code,
            "direction" => $buyer->type === "ping_post" ? "post" : "single",
            "status" => $status,
            "is_duplicate" => $duplicate !== null,
            "duplicate_of_id" => $duplicate?->id,
            "duplicate_window" => config("admin.duplicate_window_days") . "d",
            "http_status" => $httpStatus,
            "ping_id" => $pingId,
            "forwarding_number" => $forwardingNumber,
            "payout" => $payout,
            "bid_amount" => $bidAmount,
            "payload_json" => $leadData,
            "response_json" => $responseJson,
            "response_raw" => $responseRaw,
        ]);

        $googleLogger->log([
            "endpoint" => $buyer->code,
            "lead" => $leadData,
            "payload" => $result,
            "response" => $result,
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
