<?php

namespace App\Services;

use App\Models\Buyer;
use App\Models\BuyerField;
use Illuminate\Support\Arr;

class BuyerRequestService
{
    public function submitDirection(Buyer $buyer, array $leadData, string $direction, array $context = []): array
    {
        if ($direction === "ping") {
            $payload = $this->buildPayload($buyer, $leadData, "ping", $context);
            $result = $this->sendRequest($buyer, $buyer->ping_url, $payload);
            return ["ping" => $result];
        }

        if ($direction === "post") {
            $payload = $this->buildPayload($buyer, $leadData, "post", $context);
            $result = $this->sendRequest($buyer, $buyer->post_url, $payload);
            return ["post" => $result];
        }

        $payload = $this->buildPayload($buyer, $leadData, "single", $context);
        $result = $this->sendRequest($buyer, $buyer->post_url, $payload);
        return ["upstream" => $result];
    }

    public function submit(Buyer $buyer, array $leadData): array
    {
        if ($buyer->code === "D2") {
            $payload = $this->buildD2Payload($leadData);
            $result = $this->sendRequest($buyer, $buyer->post_url, $payload);
            return ["upstream" => $result];
        }

        if ($buyer->type === "ping_post" && $buyer->ping_url) {
            $pingPayload = $this->buildPayload($buyer, $leadData, "ping");
            $pingResult = $this->sendRequest($buyer, $buyer->ping_url, $pingPayload);
            $pingId = $this->extractPingId($pingResult);
            $leadId = $this->extractLeadId($pingResult);

            if (($pingId || $leadId) && $buyer->post_url) {
                $context = ["ping_id" => $pingId, "lead_id" => $leadId];
                $postPayload = $this->buildPayload($buyer, $leadData, "post", $context);
                if ($pingId && !array_key_exists("ping_id", $postPayload)) {
                    $postPayload["ping_id"] = $pingId;
                }
                $postResult = $this->sendRequest($buyer, $buyer->post_url, $postPayload);

                return [
                    "ping" => $pingResult,
                    "post" => $postResult,
                    "ping_id" => $pingId,
                    "lead_id" => $leadId,
                ];
            }

            return [
                "ping" => $pingResult,
                "ping_id" => $pingId,
                "lead_id" => $leadId,
            ];
        }

        $payload = $this->buildPayload($buyer, $leadData, "single");
        $result = $this->sendRequest($buyer, $buyer->post_url, $payload);

        return [
            "upstream" => $result,
        ];
    }

    public function buildPayload(Buyer $buyer, array $leadData, string $direction, array $context = []): array
    {
        $fields = BuyerField::query()
            ->where("buyer_id", $buyer->id)
            ->whereIn("direction", [$direction, "single"])
            ->get();

        $payload = [];

        foreach ($fields as $field) {
            $value = null;

            if ($field->source_type === "static") {
                $value = $field->source_value;
            } elseif ($field->source_type === "computed") {
                $value = $this->computedValue($field->source_key, $leadData, $context);
            } else {
                $key = $field->source_key ?: $field->field_name;
                $value = Arr::get($leadData, $key);
            }

            if ($value !== null && $value !== "") {
                $payload[$field->field_name] = $value;
            }
        }

        return $payload;
    }

    public function sendRequest(Buyer $buyer, ?string $url, array $payload): array
    {
        if (!$url) {
            return ["ok" => false, "status" => 0, "body" => "Missing endpoint URL"];
        }

        $headers = [];
        if (is_array($buyer->headers_json)) {
            foreach ($buyer->headers_json as $key => $header) {
                if (is_string($key) && !is_int($key)) {
                    $headers[] = $key . ": " . $header;
                    continue;
                }
                $headers[] = $header;
            }
        }

        $isJson = $buyer->payload_format === "json";
        $isXml = $buyer->payload_format === "xml";
        $body = $payload;
        if ($isXml) {
            $body = $this->toXml($payload);
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $isJson ? json_encode($payload) : ($isXml ? $body : http_build_query($payload)),
            CURLOPT_HTTPHEADER => array_merge($headers, [
                $isJson ? "Content-Type: application/json" : ($isXml ? "Content-Type: application/xml" : "Content-Type: application/x-www-form-urlencoded"),
            ]),
            CURLOPT_TIMEOUT => 20,
        ]);

        $rawBody = curl_exec($ch);
        if ($rawBody === false) {
            $err = curl_error($ch);
            curl_close($ch);
            return ["ok" => false, "status" => 0, "body" => $err];
        }

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = [
            "ok" => ($status >= 200 && $status < 300),
            "status" => $status,
            "body" => $rawBody,
        ];

        $decoded = $this->tryDecode($rawBody);
        if (is_array($decoded)) {
            $result["body_json"] = $decoded;
            if (isset($decoded["outcome"]) && $decoded["outcome"] === "failure") {
                $result["ok"] = false;
            }
        }

        $result["effective_ok"] = (bool) $result["ok"];

        return $result;
    }

    private function computedValue(?string $key, array $leadData, array $context)
    {
        if ($key === "ping_id") return $context["ping_id"] ?? null;
        if ($key === "lead_id") return $context["lead_id"] ?? null;
        if ($key === "now_iso") return gmdate("c");
        if ($key === "jornaya_or_cert") {
            return $leadData["jornaya_leadid"] ?? $leadData["cert_id"] ?? null;
        }
        return null;
    }

    private function tryDecode(string $body): ?array
    {
        $decoded = json_decode($body, true);
        if (is_array($decoded)) return $decoded;

        $trimmed = trim($body);
        if ($trimmed !== "" && $trimmed[0] === "<") {
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($trimmed, "SimpleXMLElement", LIBXML_NOCDATA);
            if ($xml !== false) {
                $json = json_encode($xml);
                if ($json !== false) {
                    $array = json_decode($json, true);
                    if (is_array($array)) return $array;
                }
            }
        }

        return null;
    }

    private function extractPingId(array $result): ?string
    {
        if (!isset($result["body_json"]) || !is_array($result["body_json"])) {
            return null;
        }
        $data = $result["body_json"];
        return $data["ping_id"]
            ?? $data["pingId"]
            ?? $data["try_all_buyers_ping_id"]
            ?? ($data["try_all_buyers"]["ping_id"] ?? null)
            ?? ($data["buyers"][0]["ping_id"] ?? null);
    }

    private function extractLeadId(array $result): ?string
    {
        if (!isset($result["body_json"]) || !is_array($result["body_json"])) {
            return null;
        }
        $data = $result["body_json"];
        if (isset($data["lead_id"])) return (string) $data["lead_id"];
        if (isset($data["leadId"])) return (string) $data["leadId"];
        if (isset($data["response"]) && is_array($data["response"])) {
            if (isset($data["response"]["lead_id"])) return (string) $data["response"]["lead_id"];
            if (isset($data["response"]["leadId"])) return (string) $data["response"]["leadId"];
        }
        return null;
    }

    private function buildD2Payload(array $leadData): array
    {
        $fields = [
            [
                "ref" => "incident_date_option_b",
                "title" => "When did the accident happen?",
                "answer" => $leadData["incident_date_option_b"] ?? "",
            ],
            [
                "ref" => "injury_cause",
                "title" => "What caused your injury?",
                "answer" => $leadData["injury_cause"] ?? "",
            ],
            [
                "ref" => "primary_injury",
                "title" => "Did you sustain any of the following?",
                "answer" => $leadData["primary_injury"] ?? "",
            ],
            [
                "ref" => "role_in_accident",
                "title" => "Were you the driver, passenger or pedestrian?",
                "answer" => $leadData["role_in_accident"] ?? "",
            ],
            [
                "ref" => "were_you_injured",
                "title" => "Were you injured in an Auto Accident?",
                "answer" => "Yes",
            ],
            [
                "ref" => "were_you_at_fault",
                "title" => "Were you placed at fault for the accident?",
                "answer" => "No",
            ],
            [
                "ref" => "expressed_interest",
                "title" => "Do you want to speak to an Attorney?",
                "answer" => "Yes",
            ],
            [
                "ref" => "have_attorney",
                "title" => "Do you have an attorney?",
                "answer" => "No",
            ],
            [
                "ref" => "doctor_treatment",
                "title" => "Did the injury require hospitalization, medical treatment, surgery or cause you to miss work?",
                "answer" => "Yes",
            ],
            [
                "ref" => "accident_vehicle_count",
                "title" => "How many cars were involved in the accident?",
                "answer" => "2",
            ],
            [
                "ref" => "settled_insurance",
                "title" => "Have you settled with the insurance company regarding your injuries?",
                "answer" => "No",
            ],
            [
                "ref" => "signed_retainer",
                "title" => "Have you ever signed a retainer with a law firm regarding this case?",
                "answer" => "No",
            ],
            [
                "ref" => "driver_insurance",
                "title" => "Did the other driver, who was at fault, have auto insurance?",
                "answer" => "Yes",
            ],
        ];

        return [
            "arrived_at" => gmdate("c"),
            "test_mode" => "false",
            "deal" => "4naA7Klbd3QD25QzJMxe8oZXBVPvgy",
            "lead_first_name" => $leadData["first_name"] ?? "",
            "lead_last_name" => $leadData["last_name"] ?? "",
            "lead_phone" => $leadData["phone"] ?? "",
            "case_type" => "Auto Accident",
            "zip_code" => $leadData["zip5"] ?? "",
            "certificate_type" => "TrustedForm",
            "certificate_id" => $leadData["cert_id"] ?? "",
            "certificate_url" => $leadData["cert_url"] ?? "",
            "source_url" => $leadData["source_url"] ?? "",
            "ip_address" => $leadData["ip_address"] ?? "",
            "fields" => $fields,
        ];
    }

    private function toXml(array $payload): string
    {
        $xml = new \SimpleXMLElement("<request/>");
        foreach ($payload as $key => $value) {
            $xml->addChild($key, htmlspecialchars((string) $value));
        }
        return $xml->asXML() ?: "";
    }
}
