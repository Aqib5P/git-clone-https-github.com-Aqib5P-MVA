<?php

namespace App\Services;

class BuyerResponseParser
{
    public function parse(?array $response, array $rules = []): array
    {
        $status = "unknown";
        $httpStatus = null;
        $bodyJson = null;
        $bodyRaw = "";
        $pingId = null;
        $forwarding = null;
        $payout = null;
        $bidAmount = null;
        $duration = null;

        if (is_array($response)) {
            $httpStatus = $response["status"] ?? null;
            $bodyRaw = is_string($response["body"] ?? null) ? $response["body"] : "";
            if (isset($response["body_json"]) && is_array($response["body_json"])) {
                $bodyJson = $response["body_json"];
            } elseif ($bodyRaw) {
                $decoded = $this->tryJsonDecode($bodyRaw);
                if (is_array($decoded)) $bodyJson = $decoded;
            }
        }

        if (is_array($bodyJson)) {
            $pingId = $this->extractPingId($bodyJson);
            $forwardingKeys = $rules["forwarding_keys"] ?? null;
            $payoutKeys = $rules["payout_keys"] ?? null;
            $bidKeys = $rules["bid_keys"] ?? null;
            $durationKeys = $rules["duration_keys"] ?? null;
            $forwarding = $this->extractForwardingNumber($bodyJson, $forwardingKeys && count($forwardingKeys) ? $forwardingKeys : []);
            $payout = $this->extractNumber($bodyJson, $payoutKeys && count($payoutKeys) ? $payoutKeys : ["payout", "price", "offer_conversion_payout", "bidAmount", "bidPrice"]);
            $bidAmount = $this->extractNumber($bodyJson, $bidKeys && count($bidKeys) ? $bidKeys : ["bidAmount", "bid_amount", "bidPrice"]);
            $duration = $this->extractNumber($bodyJson, $durationKeys && count($durationKeys) ? $durationKeys : ["duration", "current_conversion_duration", "min_duration", "callMinDuration"]);
            $status = $this->inferStatusFromJson($bodyJson, $rules);
        }

        if ($bodyRaw && $status === "unknown") {
            $xml = $this->tryXmlDecode($bodyRaw);
            if ($xml) {
                $result = $this->xmlValue($xml, ["result"]);
                $message = $this->xmlValue($xml, ["msg", "message", "status"]);
                $statusText = $this->inferStatusFromText(trim($result . " " . $message), $rules);
                if ($statusText) $status = strtolower($statusText);
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
                $duration = $duration ?? $this->xmlValue($xml, ["duration", "call_duration", "min_duration"]);
            }
        }

        if ($status === "unknown" && !$bodyRaw && !$bodyJson && is_array($response) && array_key_exists("effective_ok", $response)) {
            $status = $response["effective_ok"] ? "accepted" : "rejected";
        }

        if ($status === "unknown") {
            $text = "";
            if (is_array($bodyJson)) {
                $text = json_encode($bodyJson) ?: "";
            } elseif ($bodyRaw) {
                $text = $bodyRaw;
            }
            $statusText = $this->inferStatusFromText($text, $rules);
            if ($statusText) $status = strtolower($statusText);
        }

        return [
            "status" => $status,
            "http_status" => $httpStatus,
            "ping_id" => $pingId,
            "forwarding_number" => $this->normalizePhone($forwarding),
            "payout" => $payout,
            "bid_amount" => $bidAmount,
            "duration" => $duration,
            "body_json" => $bodyJson,
            "body_raw" => $bodyRaw,
        ];
    }

    private function inferStatusFromJson(array $data, array $rules = []): string
    {
        if (isset($data["outcome"]) && $data["outcome"] === "failure") {
            return "rejected";
        }
        if (!empty($data["rejectReason"]) || !empty($data["reject_reason"])) {
            return "rejected";
        }
        if (!empty($data["errors"])) {
            return "rejected";
        }
        if (isset($data["outcome"]) && $data["outcome"] === "success") {
            return "accepted";
        }
        if (isset($data["status"])) {
            $status = $this->inferStatusFromText((string) $data["status"], $rules);
            if ($status) return strtolower($status);
        }
        if (isset($data["success"]) && is_bool($data["success"])) {
            return $data["success"] ? "accepted" : "rejected";
        }
        if ((isset($data["bidAmount"]) || isset($data["bidPrice"])) && empty($data["rejectReason"]) && empty($data["reject_reason"])) {
            $bid = $data["bidAmount"] ?? $data["bidPrice"];
            if (is_numeric($bid) && (float) $bid >= 0) {
                return "accepted";
            }
        }
        if (isset($data["message"])) {
            $status = $this->inferStatusFromText((string) $data["message"], $rules);
            if ($status) return strtolower($status);
        }
        if (isset($data["msg"])) {
            $status = $this->inferStatusFromText((string) $data["msg"], $rules);
            if ($status) return strtolower($status);
        }
        if (isset($data["buyers"]) && is_array($data["buyers"]) && count($data["buyers"]) === 0) {
            return "rejected";
        }
        return "unknown";
    }

    private function inferStatusFromText(string $text, array $rules = []): string
    {
        $t = strtolower($text);
        $reject = array_map("strtolower", $rules["reject"] ?? []);
        foreach ($reject as $term) {
            if ($term !== "" && str_contains($t, $term)) {
                return "Rejected";
            }
        }
        $accept = array_map("strtolower", $rules["accept"] ?? []);
        foreach ($accept as $term) {
            if ($term !== "" && str_contains($t, $term)) {
                return "Accepted";
            }
        }
        if (str_contains($t, "accept") || str_contains($t, "success") || str_contains($t, "created") || str_contains($t, "approved") || str_contains($t, "matched")) {
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

    private function extractPingId(array $data): ?string
    {
        $keys = ["ping_id", "pingId", "id", "try_all_buyers_ping_id"];
        foreach ($keys as $key) {
            if (isset($data[$key])) return (string) $data[$key];
        }
        if (isset($data["try_all_buyers"]["ping_id"])) return (string) $data["try_all_buyers"]["ping_id"];
        if (isset($data["buyers"][0]["ping_id"])) return (string) $data["buyers"][0]["ping_id"];
        return null;
    }

    private function extractForwardingNumber(array $data, array $keys = []): ?string
    {
        $keys = $keys ?: [
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
        return null;
    }

    private function extractNumber(array $data, array $keys): ?float
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

    private function normalizePhone(?string $value): ?string
    {
        if (!$value) return null;
        $raw = trim($value);
        if ($raw === "") return null;
        if (str_starts_with($raw, "+")) return $raw;
        if (preg_match("/^[0-9]+$/", $raw)) return "+" . $raw;
        return $raw;
    }
}
