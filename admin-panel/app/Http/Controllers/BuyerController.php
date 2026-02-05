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
        return view("buyers.index", ["buyers" => $buyers]);
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

        return view("buyers.edit", [
            "buyer" => $buyer,
            "fields" => $fields,
            "leadFields" => $leadFields,
            "products" => $products,
            "campaigns" => $campaigns,
            "publishers" => $publishers,
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
        $data = $request->validate([
            "lead_json" => ["required", "string"],
        ]);

        $leadData = json_decode($data["lead_json"], true);
        if (!is_array($leadData)) {
            return redirect()->route("buyers.edit", $buyer)->withErrors(["lead_json" => "Invalid JSON."]);
        }

        $result = $service->submit($buyer, $leadData);
        $primary = $result["post"] ?? $result["upstream"] ?? $result["ping"] ?? null;
        $parsed = $parser->parse(is_array($primary) ? $primary : null, $buyer->response_rules ?? []);

        return redirect()->route("buyers.edit", $buyer)->with("test_result", [
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
        return [
            "accept" => $this->splitCsv($request->input("response_accept")),
            "reject" => $this->splitCsv($request->input("response_reject")),
            "forwarding_keys" => $this->splitCsv($request->input("forwarding_keys")),
            "payout_keys" => $this->splitCsv($request->input("payout_keys")),
            "bid_keys" => $this->splitCsv($request->input("bid_keys")),
        ];
    }

    private function splitCsv(?string $value): array
    {
        if (!$value) return [];
        return array_values(array_filter(array_map("trim", explode(",", $value))));
    }
}
