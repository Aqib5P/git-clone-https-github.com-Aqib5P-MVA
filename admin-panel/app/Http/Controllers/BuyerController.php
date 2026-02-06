<?php

namespace App\Http\Controllers;

use App\Models\Buyer;
use App\Models\BuyerField;
use App\Models\Campaign;
use App\Models\LeadField;
use App\Models\Product;
use App\Models\Publisher;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\BuyerRequestService;
use App\Services\BuyerResponseParser;
use App\Services\BuyerTemplateService;

class BuyerController extends Controller
{
    public function index()
    {
        $buyers = Buyer::orderBy("code")->get();
        $stats = BuyerField::query()
            ->selectRaw("buyer_id, count(*) as total_fields")
            ->groupBy("buyer_id")
            ->get()
            ->keyBy("buyer_id");
        $payouts = \App\Models\Attempt::query()
            ->selectRaw("buyer_id, sum(payout) as total_payout, max(payout) as max_payout, count(*) as attempts")
            ->groupBy("buyer_id")
            ->get()
            ->keyBy("buyer_id");

        return view("buyers.index", [
            "buyers" => $buyers,
            "stats" => $stats,
            "payouts" => $payouts,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            "code" => ["required", "string", "max:50"],
            "name" => ["required", "string", "max:100"],
            "type" => ["required", "string", "max:50"],
            "scope" => ["required", "string", "max:50"],
            "payload_format" => ["required", "string", "max:20"],
            "ping_url" => ["nullable", "string"],
            "post_url" => ["nullable", "string"],
            "platform" => ["nullable", "string", "max:50"],
            "response_accept" => ["nullable", "string"],
            "response_reject" => ["nullable", "string"],
            "forwarding_keys" => ["nullable", "string"],
            "payout_keys" => ["nullable", "string"],
            "bid_keys" => ["nullable", "string"],
            "duration_keys" => ["nullable", "string"],
            "response_accept_single" => ["nullable", "string"],
            "response_reject_single" => ["nullable", "string"],
            "forwarding_keys_single" => ["nullable", "string"],
            "payout_keys_single" => ["nullable", "string"],
            "bid_keys_single" => ["nullable", "string"],
            "duration_keys_single" => ["nullable", "string"],
            "response_accept_ping" => ["nullable", "string"],
            "response_reject_ping" => ["nullable", "string"],
            "forwarding_keys_ping" => ["nullable", "string"],
            "payout_keys_ping" => ["nullable", "string"],
            "bid_keys_ping" => ["nullable", "string"],
            "duration_keys_ping" => ["nullable", "string"],
            "response_accept_post" => ["nullable", "string"],
            "response_reject_post" => ["nullable", "string"],
            "forwarding_keys_post" => ["nullable", "string"],
            "payout_keys_post" => ["nullable", "string"],
            "bid_keys_post" => ["nullable", "string"],
            "duration_keys_post" => ["nullable", "string"],
            "default_product_id" => ["nullable", "integer"],
            "default_campaign_id" => ["nullable", "integer"],
            "default_publisher_id" => ["nullable", "integer"],
            "static_payout" => ["nullable", "numeric"],
            "payout_type" => ["nullable", "string", "max:20"],
            "payout_model" => ["nullable", "string", "max:20"],
            "payment_terms" => ["nullable", "string", "max:50"],
            "active" => ["nullable"],
        ]);

        $responseRules = $this->buildResponseRules($request);

        $buyer = Buyer::create([
            "code" => strtoupper($data["code"]),
            "name" => $data["name"],
            "type" => $data["type"],
            "scope" => $data["scope"],
            "payload_format" => $data["payload_format"],
            "ping_url" => $data["ping_url"] ?? null,
            "post_url" => $data["post_url"] ?? null,
            "platform" => $data["platform"] ?? "custom",
            "response_rules" => $responseRules,
            "default_product_id" => $data["default_product_id"] ?? null,
            "default_campaign_id" => $data["default_campaign_id"] ?? null,
            "default_publisher_id" => $data["default_publisher_id"] ?? null,
            "static_payout" => $data["static_payout"] ?? null,
            "payout_type" => $data["payout_type"] ?? "dynamic",
            "payout_model" => $data["payout_model"] ?? "CPL",
            "payment_terms" => $data["payment_terms"] ?? null,
            "active" => $request->boolean("active"),
        ]);

        if ($request->boolean("public_enabled")) {
            $buyer->update([
                "public_enabled" => true,
                "public_token" => $buyer->public_token ?: strtoupper($buyer->code) . "-" . Str::random(8),
            ]);
        }

        return redirect()->route("buyers.edit", $buyer);
    }

    public function edit(Buyer $buyer)
    {
        $fields = $buyer->fields()->orderBy("field_name")->get();
        $leadFields = LeadField::orderBy("key")->get();
        $products = Product::orderBy("name")->get();
        $campaigns = Campaign::orderBy("name")->get();
        $publishers = Publisher::orderBy("name")->get();
        $sampleLeadSingle = $this->buildSampleLeadData($buyer, "single", $leadFields);
        $sampleLeadPing = $this->buildSampleLeadData($buyer, "ping", $leadFields);
        $sampleLeadPost = $this->buildSampleLeadData($buyer, "post", $leadFields);

        return view("buyers.edit", [
            "buyer" => $buyer,
            "fields" => $fields,
            "leadFields" => $leadFields,
            "products" => $products,
            "campaigns" => $campaigns,
            "publishers" => $publishers,
            "sampleLeadSingle" => $sampleLeadSingle,
            "sampleLeadPing" => $sampleLeadPing,
            "sampleLeadPost" => $sampleLeadPost,
        ]);
    }

    public function update(Request $request, Buyer $buyer)
    {
        $data = $request->validate([
            "name" => ["required", "string", "max:100"],
            "type" => ["required", "string", "max:50"],
            "scope" => ["required", "string", "max:50"],
            "payload_format" => ["required", "string", "max:20"],
            "ping_url" => ["nullable", "string"],
            "post_url" => ["nullable", "string"],
            "headers_json" => ["nullable", "string"],
            "platform" => ["nullable", "string", "max:50"],
            "response_accept" => ["nullable", "string"],
            "response_reject" => ["nullable", "string"],
            "forwarding_keys" => ["nullable", "string"],
            "payout_keys" => ["nullable", "string"],
            "bid_keys" => ["nullable", "string"],
            "duration_keys" => ["nullable", "string"],
            "response_accept_single" => ["nullable", "string"],
            "response_reject_single" => ["nullable", "string"],
            "forwarding_keys_single" => ["nullable", "string"],
            "payout_keys_single" => ["nullable", "string"],
            "bid_keys_single" => ["nullable", "string"],
            "duration_keys_single" => ["nullable", "string"],
            "response_accept_ping" => ["nullable", "string"],
            "response_reject_ping" => ["nullable", "string"],
            "forwarding_keys_ping" => ["nullable", "string"],
            "payout_keys_ping" => ["nullable", "string"],
            "bid_keys_ping" => ["nullable", "string"],
            "duration_keys_ping" => ["nullable", "string"],
            "response_accept_post" => ["nullable", "string"],
            "response_reject_post" => ["nullable", "string"],
            "forwarding_keys_post" => ["nullable", "string"],
            "payout_keys_post" => ["nullable", "string"],
            "bid_keys_post" => ["nullable", "string"],
            "duration_keys_post" => ["nullable", "string"],
            "default_product_id" => ["nullable", "integer"],
            "default_campaign_id" => ["nullable", "integer"],
            "default_publisher_id" => ["nullable", "integer"],
            "static_payout" => ["nullable", "numeric"],
            "payout_type" => ["nullable", "string", "max:20"],
            "payout_model" => ["nullable", "string", "max:20"],
            "payment_terms" => ["nullable", "string", "max:50"],
            "active" => ["nullable"],
        ]);

        $responseRules = $this->buildResponseRules($request);

        $buyer->update([
            "name" => $data["name"],
            "type" => $data["type"],
            "scope" => $data["scope"],
            "payload_format" => $data["payload_format"],
            "ping_url" => $data["ping_url"] ?? null,
            "post_url" => $data["post_url"] ?? null,
            "headers_json" => $data["headers_json"] ? json_decode($data["headers_json"], true) : null,
            "platform" => $data["platform"] ?? $buyer->platform,
            "response_rules" => $responseRules,
            "default_product_id" => $data["default_product_id"] ?? null,
            "default_campaign_id" => $data["default_campaign_id"] ?? null,
            "default_publisher_id" => $data["default_publisher_id"] ?? null,
            "static_payout" => $data["static_payout"] ?? null,
            "payout_type" => $data["payout_type"] ?? "dynamic",
            "payout_model" => $data["payout_model"] ?? "CPL",
            "payment_terms" => $data["payment_terms"] ?? null,
            "active" => $request->boolean("active"),
            "public_enabled" => $request->boolean("public_enabled"),
        ]);

        if ($request->boolean("public_enabled") && !$buyer->public_token) {
            $buyer->update([
                "public_token" => strtoupper($buyer->code) . "-" . Str::random(8),
            ]);
        }

        return redirect()->route("buyers.edit", $buyer);
    }

    public function toggle(Buyer $buyer)
    {
        $buyer->update(["active" => !$buyer->active]);
        return redirect()->route("buyers.index");
    }

    public function regenerateToken(Buyer $buyer)
    {
        $buyer->update([
            "public_token" => strtoupper($buyer->code) . "-" . Str::random(8),
            "public_enabled" => true,
        ]);

        return redirect()->route("buyers.edit", $buyer);
    }

    public function applyTemplate(Request $request, Buyer $buyer, BuyerTemplateService $service)
    {
        $data = $request->validate([
            "template_key" => ["required", "string"],
            "replace" => ["nullable"],
        ]);

        $service->applyTemplate($buyer, $data["template_key"], $request->boolean("replace"));

        return redirect()->route("buyers.edit", $buyer);
    }

    public function test(Request $request, Buyer $buyer, BuyerRequestService $service, BuyerResponseParser $parser)
    {
        $leadData = $this->decodeLeadData($request, "lead_json", "lead_fields_single");
        if (!$leadData) {
            return redirect()->route("buyers.edit", $buyer)->withErrors(["lead_json" => "Invalid JSON."]);
        }

        $result = $service->submitDirection($buyer, $leadData, "single");
        $primary = $result["upstream"] ?? null;
        $parsed = $parser->parse(is_array($primary) ? $primary : null, $this->rulesFor($buyer, "single"));

        return redirect()->route("buyers.edit", $buyer)->with("test_result_single", [
            "raw" => $result,
            "parsed" => $parsed,
        ]);
    }

    public function testPing(Request $request, Buyer $buyer, BuyerRequestService $service, BuyerResponseParser $parser)
    {
        $leadData = $this->decodeLeadData($request, "lead_json_ping", "lead_fields_ping");
        if (!$leadData) {
            return redirect()->route("buyers.edit", $buyer)->withErrors(["lead_json_ping" => "Invalid JSON."]);
        }

        $result = $service->submitDirection($buyer, $leadData, "ping");
        $parsed = $parser->parse(is_array($result["ping"] ?? null) ? $result["ping"] : null, $this->rulesFor($buyer, "ping"));

        return redirect()->route("buyers.edit", $buyer)->with("test_result_ping", [
            "raw" => $result,
            "parsed" => $parsed,
        ]);
    }

    public function testPost(Request $request, Buyer $buyer, BuyerRequestService $service, BuyerResponseParser $parser)
    {
        $leadData = $this->decodeLeadData($request, "lead_json_post", "lead_fields_post");
        if (!$leadData) {
            return redirect()->route("buyers.edit", $buyer)->withErrors(["lead_json_post" => "Invalid JSON."]);
        }

        $context = array_filter([
            "ping_id" => $request->input("ping_id"),
            "lead_id" => $request->input("lead_id"),
        ]);

        $result = $service->submitDirection($buyer, $leadData, "post", $context);
        $parsed = $parser->parse(is_array($result["post"] ?? null) ? $result["post"] : null, $this->rulesFor($buyer, "post"));

        return redirect()->route("buyers.edit", $buyer)->with("test_result_post", [
            "raw" => $result,
            "parsed" => $parsed,
        ]);
    }

    public function storeField(Request $request, Buyer $buyer)
    {
        $data = $request->validate([
            "field_name" => ["required", "string", "max:100"],
            "direction" => ["required", "string", "max:20"],
            "source_type" => ["required", "string", "max:20"],
            "source_key" => ["nullable", "string", "max:100"],
            "source_value" => ["nullable", "string"],
            "required" => ["nullable"],
        ]);

        BuyerField::create([
            "buyer_id" => $buyer->id,
            "direction" => $data["direction"],
            "field_name" => $data["field_name"],
            "source_type" => $data["source_type"],
            "source_key" => $data["source_key"] ?? ($data["source_type"] === "lead" ? $data["field_name"] : null),
            "source_value" => $data["source_value"] ?? null,
            "required" => $request->boolean("required"),
        ]);

        return redirect()->route("buyers.edit", $buyer);
    }

    public function updateField(Request $request, BuyerField $field)
    {
        $data = $request->validate([
            "required" => ["nullable"],
            "direction" => ["nullable", "string", "max:20"],
            "source_type" => ["nullable", "string", "max:20"],
            "source_key" => ["nullable", "string", "max:100"],
            "source_value" => ["nullable", "string"],
        ]);

        if (array_key_exists("required", $data)) {
            $field->required = $request->boolean("required");
        }
        if (isset($data["direction"])) {
            $field->direction = $data["direction"];
        }
        if (isset($data["source_type"])) {
            $field->source_type = $data["source_type"];
        }
        if (array_key_exists("source_key", $data)) {
            $field->source_key = $data["source_key"] ?: null;
        }
        if (array_key_exists("source_value", $data)) {
            $field->source_value = $data["source_value"] ?: null;
        }
        $field->save();

        return redirect()->route("buyers.edit", $field->buyer_id);
    }

    public function deleteField(BuyerField $field)
    {
        $buyerId = $field->buyer_id;
        $field->delete();

        return redirect()->route("buyers.edit", $buyerId);
    }

    private function buildResponseRules(Request $request): array
    {
        $rules = [];

        $single = $this->buildRuleSet($request, "single");
        $ping = $this->buildRuleSet($request, "ping");
        $post = $this->buildRuleSet($request, "post");

        if ($single) $rules["single"] = $single;
        if ($ping) $rules["ping"] = $ping;
        if ($post) $rules["post"] = $post;

        if (!empty($rules)) {
            return $rules;
        }

        return [
            "accept" => $this->splitCsv($request->input("response_accept")),
            "reject" => $this->splitCsv($request->input("response_reject")),
            "forwarding_keys" => $this->splitCsv($request->input("forwarding_keys")),
            "payout_keys" => $this->splitCsv($request->input("payout_keys")),
            "bid_keys" => $this->splitCsv($request->input("bid_keys")),
            "duration_keys" => $this->splitCsv($request->input("duration_keys")),
        ];
    }

    private function buildRuleSet(Request $request, string $suffix): array
    {
        $suffix = "_" . $suffix;
        $set = [
            "accept" => $this->splitCsv($request->input("response_accept" . $suffix)),
            "reject" => $this->splitCsv($request->input("response_reject" . $suffix)),
            "forwarding_keys" => $this->splitCsv($request->input("forwarding_keys" . $suffix)),
            "payout_keys" => $this->splitCsv($request->input("payout_keys" . $suffix)),
            "bid_keys" => $this->splitCsv($request->input("bid_keys" . $suffix)),
            "duration_keys" => $this->splitCsv($request->input("duration_keys" . $suffix)),
        ];

        $hasValues = false;
        foreach ($set as $values) {
            if (!empty($values)) {
                $hasValues = true;
                break;
            }
        }
        return $hasValues ? $set : [];
    }

    private function rulesFor(Buyer $buyer, string $direction): array
    {
        $rules = $buyer->response_rules ?? [];
        if (!is_array($rules)) return [];
        if ($direction === "single" && isset($rules["single"])) return $rules["single"];
        if (isset($rules[$direction])) return $rules[$direction];
        return $rules;
    }

    private function decodeLeadData(Request $request, string $jsonField, string $arrayField): ?array
    {
        $array = $request->input($arrayField);
        if (is_array($array)) {
            $filtered = array_filter($array, fn ($value) => $value !== null && $value !== "");
            if (!empty($filtered)) return $filtered;
        }

        $raw = $request->input($jsonField);
        if (!is_string($raw) || trim($raw) === "") return null;
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) return null;
        return $decoded;
    }

    private function buildSampleLeadData(Buyer $buyer, string $direction, $leadFields): array
    {
        $fields = $buyer->fields()
            ->where("source_type", "lead")
            ->whereIn("direction", [$direction, "single"])
            ->get();

        if ($fields->isEmpty()) {
            return [
                "first_name" => "John",
                "last_name" => "Doe",
                "phone" => "5551234567",
                "zip5" => "90210",
                "email" => "john@example.com",
            ];
        }

        $leadFieldMap = $leadFields->keyBy("key");
        $data = [];
        foreach ($fields as $field) {
            $key = $field->source_key ?: $field->field_name;
            if (!$key || isset($data[$key])) continue;
            $meta = $leadFieldMap->get($key);
            $data[$key] = $this->sampleValueForKey($key, $meta?->type, $meta?->options);
        }

        return $data;
    }

    private function sampleValueForKey(string $key, ?string $type, $options)
    {
        $defaults = [
            "first_name" => "John",
            "last_name" => "Doe",
            "email" => "john@example.com",
            "phone" => "5551234567",
            "zip5" => "90210",
            "zip" => "90210",
            "state" => "CA",
            "city" => "Los Angeles",
            "address" => "100 Main St",
            "cert_id" => "CERT-TEST-123",
            "cert_url" => "https://cert.example.com/test",
            "dob" => "1990-01-01",
        ];

        if (isset($defaults[$key])) return $defaults[$key];

        if ($type === "select" && is_array($options) && count($options)) {
            $values = array_values($options);
            return $values[0];
        }
        if ($type === "email") return "john@example.com";
        if ($type === "tel") return "5551234567";
        if ($type === "date") return date("Y-m-d");
        if ($type === "url") return "https://example.com";

        return "Test";
    }

    private function splitCsv(?string $value): array
    {
        if (!$value) return [];
        return array_values(array_filter(array_map("trim", explode(",", $value))));
    }
}
