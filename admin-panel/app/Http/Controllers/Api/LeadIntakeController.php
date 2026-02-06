<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\Buyer;
use App\Models\Lead;
use App\Services\DuplicateChecker;
use Illuminate\Http\Request;

class LeadIntakeController extends Controller
{
    public function store(Request $request, DuplicateChecker $duplicateChecker)
    {
        $token = config("admin.intake_token");
        if ($token && $request->header("X-Api-Token") !== $token) {
            return response()->json(["error" => "Unauthorized"], 401);
        }

        $payload = $request->json()->all();
        $endpoint = $payload["endpoint"] ?? $payload["buyer"] ?? null;
        $leadData = $payload["lead"] ?? $payload["data"] ?? [];
        $payloadData = $payload["payload"] ?? null;
        $responseData = $payload["response"] ?? $payload["upstream"] ?? null;
        $pingData = $payload["ping"] ?? null;
        $postData = $payload["post"] ?? null;

        $buyer = null;
        if ($endpoint) {
            $buyer = Buyer::where("code", strtoupper($endpoint))->first();
        }

        $lead = Lead::create([
            "product_id" => $buyer?->default_product_id,
            "campaign_id" => $buyer?->default_campaign_id,
            "publisher_id" => $buyer?->default_publisher_id,
            "first_name" => $leadData["first_name"] ?? null,
            "last_name" => $leadData["last_name"] ?? null,
            "email" => $leadData["email"] ?? null,
            "phone" => $leadData["phone"] ?? null,
            "zip5" => $leadData["zip5"] ?? ($leadData["zip"] ?? null),
            "city" => $leadData["city"] ?? null,
            "state" => $leadData["state"] ?? null,
            "accident_state" => $leadData["accident_state"] ?? null,
            "ip_address" => $leadData["ip_address"] ?? null,
            "source_url" => $leadData["source_url"] ?? null,
            "cert_id" => $leadData["cert_id"] ?? null,
            "cert_url" => $leadData["cert_url"] ?? null,
            "lead_json" => $leadData,
        ]);

        $duplicate = $buyer ? $duplicateChecker->findDuplicateAttempt($buyer->id, $lead->phone) : null;

        $primaryResponse = $postData ?: ($responseData ?: $pingData);
        $direction = $postData ? "post" : ($pingData ? "ping" : "single");
        $pingParsed = $this->parseBuyerResponse($pingData);
        $postParsed = $this->parseBuyerResponse($postData);
        $parsed = $this->parseBuyerResponse($primaryResponse);

        $payout = $postParsed["payout"] ?? $pingParsed["payout"] ?? $parsed["payout"] ?? null;
        $bidAmount = $postParsed["bid_amount"] ?? $pingParsed["bid_amount"] ?? $parsed["bid_amount"] ?? null;
        $status = $parsed["status"] ?? "unknown";
        if ($buyer?->type === "ping_post" && !$postData) {
            $status = "rejected";
        }
        $rejectReason = $this->extractRejectReason($parsed["body_json"] ?? null);
        if ($status === "accepted" && $rejectReason !== "") {
            $status = "rejected";
        }

        Attempt::create([
            "lead_id" => $lead->id,
            "buyer_id" => $buyer?->id,
            "endpoint" => $endpoint,
            "direction" => $direction,
            "status" => $status,
            "is_duplicate" => $duplicate !== null,
            "duplicate_of_id" => $duplicate?->id,
            "duplicate_window" => config("admin.duplicate_window_days") . "d",
            "http_status" => $postParsed["http_status"] ?? $pingParsed["http_status"] ?? $parsed["http_status"],
            "ping_id" => $pingParsed["ping_id"] ?? $parsed["ping_id"],
            "forwarding_number" => $postParsed["forwarding_number"] ?? $pingParsed["forwarding_number"] ?? $parsed["forwarding_number"],
            "payout" => $payout,
            "bid_amount" => $bidAmount,
            "payload_json" => $payloadData,
            "response_json" => $postParsed["body_json"] ?? $pingParsed["body_json"] ?? $parsed["body_json"],
            "response_raw" => $postParsed["body_raw"] ?? $pingParsed["body_raw"] ?? $parsed["body_raw"],
        ]);

        return response()->json(["ok" => true]);
    }

    private function parseBuyerResponse($response): array
    {
        $status = "unknown";
        $httpStatus = null;
        $bodyJson = null;
        $bodyRaw = "";
        $pingId = "";
        $forwarding = "";
        $payout = null;
        $bidAmount = null;

        if (is_array($response)) {
            $httpStatus = $response["status"] ?? null;
            $bodyRaw = is_string($response["body"] ?? null) ? $response["body"] : "";
            if (isset($response["body_json"]) && is_array($response["body_json"])) {
                $bodyJson = $response["body_json"];
            } elseif ($bodyRaw) {
                $decoded = $this->tryJsonDecode($bodyRaw);
                if (is_array($decoded)) $bodyJson = $decoded;
            }
        } elseif (is_string($response)) {
            $bodyRaw = $response;
            $decoded = $this->tryJsonDecode($bodyRaw);
            if (is_array($decoded)) $bodyJson = $decoded;
        }

        if (is_array($bodyJson)) {
            $pingId = $this->extractPingId($bodyJson);
            $forwarding = $this->extractForwardingNumber($bodyJson);
            $payout = $this->extractNumber($bodyJson, ["payout", "price", "offer_conversion_payout", "bidAmount", "bidPrice"]);
            $bidAmount = $this->extractNumber($bodyJson, ["bidAmount", "bid_amount", "bidPrice"]);

            $status = $this->inferStatusFromJson($bodyJson);
        }

        if ($bodyRaw && $status === "unknown") {
            $xml = $this->tryXmlDecode($bodyRaw);
            if ($xml) {
                $result = $this->xmlValue($xml, ["result"]);
                $message = $this->xmlValue($xml, ["msg", "message", "status"]);
                $status = $this->inferStatusFromText(trim($result . " " . $message));
                $forwarding = $forwarding ?: $this->xmlValue($xml, [
                    "phone_number",
                    "phoneNumber",
                    "forwarding_number",
                    "forwardingNumber",
                    "transfer_number",
                    "transferNumber",
                    "did",
                    "destination_number",
                    "call_router_number",
                ]);
                $payout = $payout ?? $this->xmlValue($xml, ["price", "payout"]);
            }
        }

        if ($status === "unknown" && !$bodyRaw && !$bodyJson && is_array($response) && array_key_exists("effective_ok", $response)) {
            $status = $response["effective_ok"] ? "accepted" : "rejected";
        }

        return [
            "status" => $status,
            "http_status" => $httpStatus,
            "ping_id" => $pingId,
            "forwarding_number" => $this->normalizePhone($forwarding),
            "payout" => $payout,
            "bid_amount" => $bidAmount,
            "body_json" => $bodyJson,
            "body_raw" => $bodyRaw,
        ];
    }

    private function inferStatusFromJson(array $data): string
    {
        if (isset($data["outcome"]) && $data["outcome"] === "failure") {
            return "rejected";
        }
        if (isset($data["outcome"]) && $data["outcome"] === "success") {
            return "accepted";
        }
        if (isset($data["status"])) {
            $status = $this->inferStatusFromText((string) $data["status"]);
            if ($status) return strtolower($status);
        }
        if (isset($data["success"]) && is_bool($data["success"])) {
            return $data["success"] ? "accepted" : "rejected";
        }
        if (!empty($data["rejectReason"]) || !empty($data["reject_reason"])) {
            return "rejected";
        }
        if ((isset($data["bidAmount"]) || isset($data["bidPrice"])) && empty($data["rejectReason"]) && empty($data["reject_reason"])) {
            $bid = $data["bidAmount"] ?? $data["bidPrice"];
            if (is_numeric($bid) && (float) $bid >= 0) {
                return "accepted";
            }
        }
        if (!empty($data["errors"])) {
            return "rejected";
        }
        if (isset($data["message"])) {
            $status = $this->inferStatusFromText((string) $data["message"]);
            if ($status) return strtolower($status);
        }
        if (isset($data["msg"])) {
            $status = $this->inferStatusFromText((string) $data["msg"]);
            if ($status) return strtolower($status);
        }
        if (isset($data["buyers"]) && is_array($data["buyers"]) && count($data["buyers"]) === 0) {
            return "rejected";
        }
        return "unknown";
    }

    private function inferStatusFromText(string $text): string
    {
        $t = strtolower($text);
        if (str_contains($t, "accept") || str_contains($t, "success") || str_contains($t, "created") || str_contains($t, "approved")) {
            return "Accepted";
        }
        if (str_contains($t, "reject") || str_contains($t, "declin") || str_contains($t, "fail") || str_contains($t, "error") || str_contains($t, "invalid")) {
            return "Rejected";
        }
        if (str_contains($t, "no matching") || str_contains($t, "no match") || str_contains($t, "no_matching")) {
            return "Rejected";
        }
        return "";
    }

    private function tryJsonDecode(string $text)
    {
        $decoded = json_decode($text, true);
        if (is_array($decoded)) return $decoded;
        if (is_string($decoded)) {
            $decodedInner = json_decode($decoded, true);
            if (is_array($decodedInner)) return $decodedInner;
        }
        return null;
    }

    private function tryXmlDecode(string $text)
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($text);
        if ($xml === false) return null;
        return $xml;
    }

    private function xmlValue($xml, array $tags): string
    {
        foreach ($tags as $tag) {
            if (isset($xml->{$tag}) && (string) $xml->{$tag} !== "") {
                return (string) $xml->{$tag};
            }
        }
        return "";
    }

    private function extractPingId(array $data): string
    {
        $keys = ["ping_id", "pingId", "id", "try_all_buyers_ping_id"];
        foreach ($keys as $key) {
            if (isset($data[$key])) return (string) $data[$key];
        }
        if (isset($data["try_all_buyers"]["ping_id"])) return (string) $data["try_all_buyers"]["ping_id"];
        if (isset($data["buyers"][0]["ping_id"])) return (string) $data["buyers"][0]["ping_id"];
        return "";
    }

    private function extractForwardingNumber(array $data): string
    {
        $keys = [
            "phoneNumber",
            "phone_number",
            "phoneNumberNoPlus",
            "number",
            "forwarding_number",
            "forwardingNumber",
            "transfer_number",
            "transferNumber",
            "did",
            "destination_number",
            "call_router_number",
        ];
        foreach ($keys as $key) {
            if (isset($data[$key]) && $data[$key] !== "") return (string) $data[$key];
        }
        return "";
    }

    private function extractNumber(array $data, array $keys)
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

    private function normalizePhone(string $value): ?string
    {
        $raw = trim($value);
        if ($raw === "") return null;
        if (str_starts_with($raw, "+")) return $raw;
        if (preg_match("/^[0-9]+$/", $raw)) return "+" . $raw;
        return $raw;
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
