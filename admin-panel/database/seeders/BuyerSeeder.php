<?php

namespace Database\Seeders;

use App\Models\Buyer;
use App\Models\BuyerField;
use App\Models\Campaign;
use App\Models\Product;
use Illuminate\Database\Seeder;

class BuyerSeeder extends Seeder
{
    public function run(): void
    {
        $product = Product::where("code", "MVA")->first();
        $campaign = Campaign::where("code", "MVA-DEFAULT")->first();

        $buyers = [
            "D1" => ["name" => "Buyer D1", "type" => "single"],
            "D2" => ["name" => "Buyer D2", "type" => "single"],
            "D3" => ["name" => "Buyer D3", "type" => "single"],
            "D4" => ["name" => "Buyer D4", "type" => "single"],
            "D5" => ["name" => "Buyer D5", "type" => "single"],
            "D6" => ["name" => "Buyer D6", "type" => "single"],
            "D7" => ["name" => "Buyer D7", "type" => "single"],
            "D8" => ["name" => "Buyer D8", "type" => "single"],
            "D9" => ["name" => "Buyer D9", "type" => "single"],
            "D10" => ["name" => "Buyer D10", "type" => "single"],
            "D11" => ["name" => "Buyer D11", "type" => "single"],
            "D12" => ["name" => "Buyer D12", "type" => "single"],
            "D13" => ["name" => "Buyer D13", "type" => "single"],
            "D14" => ["name" => "Buyer D14", "type" => "single"],
            "D15" => ["name" => "Buyer D15", "type" => "single"],
            "D16" => ["name" => "Buyer D16", "type" => "single"],
            "D17" => ["name" => "Buyer D17", "type" => "single"],
            "D18" => ["name" => "Buyer D18", "type" => "single"],
            "D19" => ["name" => "Buyer D19", "type" => "single"],
            "D20" => ["name" => "Buyer D20", "type" => "single"],
            "D21" => ["name" => "Buyer D21", "type" => "single"],
            "D22" => ["name" => "Buyer D22", "type" => "single"],
            "D23" => ["name" => "Buyer D23 (Ringba)", "type" => "rtb"],
            "D24" => ["name" => "Buyer D24", "type" => "single"],
            "D25" => ["name" => "Buyer D25", "type" => "single"],
            "D26" => ["name" => "Buyer D26", "type" => "single"],
            "D27" => ["name" => "Buyer D27", "type" => "single"],
            "D28" => ["name" => "Buyer D28", "type" => "single"],
            "D29" => ["name" => "Buyer D29", "type" => "single"],
            "D30" => ["name" => "Buyer D30", "type" => "single"],
            "D31" => ["name" => "Buyer D31", "type" => "single"],
            "D32" => ["name" => "Buyer D32", "type" => "ping_post"],
        ];

        $required = [
            "D1" => ["first_name", "last_name", "zip5", "phone", "cert_id"],
            "D2" => ["first_name", "last_name", "phone", "zip5", "cert_id", "cert_url", "source_url", "ip_address", "incident_date_option_b", "injury_cause", "primary_injury", "role_in_accident"],
            "D6" => ["phone", "cert_url", "first_name", "last_name", "email", "state", "accident_date_mmddyyyy"],
            "D23" => ["phone", "zip5", "cert_url"],
            "D25" => ["first_name", "last_name", "email", "phone", "zip5", "city", "ip_address", "cert_id", "attorney", "fault", "injured", "doctor_treatment", "incident_date_option_b"],
            "D26" => ["phone", "first_name", "last_name", "email", "zip5", "cert_url", "cert_id", "accident_date_mmddyyyy"],
            "D27" => ["first_name", "last_name", "email", "phone", "state", "accident_state", "zip5", "source_url", "ip_address", "cert_url", "accident_sol", "accident_date_yyyy_mm_dd"],
            "D30" => ["first_name", "last_name", "email", "phone", "zip5", "ip_address", "cert_id"],
            "D32" => ["first_name", "last_name", "email", "phone", "address", "city", "state", "accident_state", "zip5", "ip_address", "source_url", "dob", "cert_id", "cert_url", "cert_type", "accident_date_yyyy_mm_dd", "injured", "doctor_treatment", "fault", "attorney", "role_in_accident"],
        ];

        $fieldMap = [
            "D1" => [
                ["lp_campaign_id", "static", null, "64c953e483f75", "single", false],
                ["lp_campaign_key", "static", null, "NxjrqXwd9cZgKBPLHhGD", "single", false],
                ["first_name", "lead", "first_name", null, "single", true],
                ["last_name", "lead", "last_name", null, "single", true],
                ["zip_code", "lead", "zip5", null, "single", true],
                ["phone_home", "lead", "phone", null, "single", true],
                ["trusted_form_cert_id", "lead", "cert_id", null, "single", true],
                ["lp_caller_id", "lead", "phone", null, "single", false],
            ],
            "D6" => [
                ["phone_1", "lead", "phone", null, "single", true],
                ["trustedform_cert_url", "lead", "cert_url", null, "single", true],
                ["first_name", "lead", "first_name", null, "single", true],
                ["last_name", "lead", "last_name", null, "single", true],
                ["email", "lead", "email", null, "single", true],
                ["state_of_accident_qmark", "lead", "state", null, "single", true],
                ["date_of_accident_qmark", "lead", "accident_date_mmddyyyy", null, "single", true],
            ],
            "D23" => [
                ["CID", "lead", "phone", null, "single", true],
                ["exposeCallerId", "static", null, "yes", "single", false],
                ["zipcode", "lead", "zip5", null, "single", true],
                ["State", "lead", "state", null, "single", false],
                ["SubID", "static", null, "hzn345", "single", false],
                ["email", "lead", "email", null, "single", false],
                ["first_name", "lead", "first_name", null, "single", false],
                ["last_name", "lead", "last_name", null, "single", false],
                ["Cert_Type", "static", null, "TrustedForm", "single", false],
                ["Cert_Id", "lead", "cert_id", null, "single", false],
                ["trusted_form_url", "lead", "cert_url", null, "single", true],
                ["call_type", "static", null, "o", "single", false],
            ],
            "D25" => [
                ["lp_campaign_id", "static", null, "6937514e0c245", "single", false],
                ["lp_campaign_key", "static", null, "BRLfV3z4Z9Hc7TgkWyKF", "single", false],
                ["first_name", "lead", "first_name", null, "single", true],
                ["last_name", "lead", "last_name", null, "single", true],
                ["zip_code", "lead", "zip5", null, "single", true],
                ["city", "lead", "city", null, "single", true],
                ["phone_home", "lead", "phone", null, "single", true],
                ["email_address", "lead", "email", null, "single", true],
                ["trusted_form_cert_id", "lead", "cert_id", null, "single", true],
                ["ip_address", "lead", "ip_address", null, "single", true],
                ["attorney", "lead", "attorney", null, "single", true],
                ["fault", "lead", "fault", null, "single", true],
                ["injured", "lead", "injured", null, "single", true],
                ["doctor_treatment", "lead", "doctor_treatment", null, "single", true],
                ["incident_date", "lead", "incident_date_option_b", null, "single", true],
                ["lp_caller_id", "lead", "phone", null, "single", false],
            ],
            "D26" => [
                ["lead_token", "static", null, "c5af0485a9a44f8c8832bbc80ea0f618", "single", false],
                ["traffic_source_id", "static", null, "1002", "single", false],
                ["caller_id", "lead", "phone", null, "single", true],
                ["first_name", "lead", "first_name", null, "single", true],
                ["last_name", "lead", "last_name", null, "single", true],
                ["email", "lead", "email", null, "single", true],
                ["zip", "lead", "zip5", null, "single", true],
                ["trusted_form_cert_url", "lead", "cert_url", null, "single", true],
                ["jornaya_leadid", "computed", "jornaya_or_cert", null, "single", false],
                ["accident_date", "lead", "accident_date_mmddyyyy", null, "single", true],
            ],
            "D27" => [
                ["first_name", "lead", "first_name", null, "single", true],
                ["last_name", "lead", "last_name", null, "single", true],
                ["email", "lead", "email", null, "single", true],
                ["caller_id", "lead", "phone", null, "single", true],
                ["state", "lead", "state", null, "single", true],
                ["accident_state", "lead", "accident_state", null, "single", true],
                ["zip", "lead", "zip5", null, "single", true],
                ["accident_date", "lead", "accident_date_yyyy_mm_dd", null, "single", true],
                ["accident_sol", "lead", "accident_sol", null, "single", true],
                ["source_url", "lead", "source_url", null, "single", true],
                ["ip_address", "lead", "ip_address", null, "single", true],
                ["trusted_form_cert_url", "lead", "cert_url", null, "single", true],
                ["have_attorney", "static", null, "No", "single", false],
                ["injury_occured", "static", null, "Yes", "single", false],
                ["cited", "static", null, "No", "single", false],
                ["injury_type", "static", null, "Other", "single", false],
                ["hospitalized_or_treated", "static", null, "Yes", "single", false],
            ],
            "D30" => [
                ["lp_campaign_id", "static", null, "6973a18b79a75", "single", false],
                ["lp_campaign_key", "static", null, "Rv8w4LDHXWd63thxk2pn", "single", false],
                ["first_name", "lead", "first_name", null, "single", true],
                ["last_name", "lead", "last_name", null, "single", true],
                ["email_address", "lead", "email", null, "single", true],
                ["zip_code", "lead", "zip5", null, "single", true],
                ["phone_home", "lead", "phone", null, "single", true],
                ["trusted_form_cert_id", "lead", "cert_id", null, "single", true],
                ["ip_address", "lead", "ip_address", null, "single", true],
                ["lp_caller_id", "lead", "phone", null, "single", false],
            ],
            "D32" => [
                ["trackdrive_number", "static", null, "+18446757519", "ping", false],
                ["traffic_source_id", "static", null, "7785", "ping", false],
                ["buyer_td_traffic_source_id", "static", null, "7785", "ping", false],
                ["has_insurance", "static", null, "Yes", "ping", false],
                ["cited", "static", null, "No", "ping", false],
                ["settlement", "static", null, "No", "ping", false],
                ["needs_attorney", "static", null, "Yes", "ping", false],
                ["claimant_relationship", "static", null, "SELF", "ping", false],
                ["caller_id", "lead", "phone", null, "ping", true],
                ["trusted_form_token", "lead", "cert_id", null, "ping", true],
                ["trusted_form_cert_url", "lead", "cert_url", null, "ping", true],
                ["cert_id", "lead", "cert_id", null, "ping", true],
                ["cert_type", "lead", "cert_type", null, "ping", true],
                ["first_name", "lead", "first_name", null, "ping", true],
                ["last_name", "lead", "last_name", null, "ping", true],
                ["email", "lead", "email", null, "ping", true],
                ["address", "lead", "address", null, "ping", true],
                ["city", "lead", "city", null, "ping", true],
                ["state", "lead", "state", null, "ping", true],
                ["zip", "lead", "zip5", null, "ping", true],
                ["accident_state", "lead", "accident_state", null, "ping", true],
                ["date_injured", "lead", "accident_date_yyyy_mm_dd", null, "ping", true],
                ["injury_occured", "lead", "injured", null, "ping", true],
                ["hospitalized_or_treated", "lead", "doctor_treatment", null, "ping", true],
                ["person_at_fault", "lead", "fault", null, "ping", true],
                ["currently_represented", "lead", "attorney", null, "ping", true],
                ["incident_position", "lead", "role_in_accident", null, "ping", true],

                ["trackdrive_number", "static", null, "+18446757519", "post", false],
                ["traffic_source_id", "static", null, "7785", "post", false],
                ["has_insurance", "static", null, "Yes", "post", false],
                ["trusted_form_token", "lead", "cert_id", null, "post", true],
                ["trusted_form_cert_url", "lead", "cert_url", null, "post", true],
                ["first_name", "lead", "first_name", null, "post", true],
                ["last_name", "lead", "last_name", null, "post", true],
                ["email", "lead", "email", null, "post", true],
                ["address", "lead", "address", null, "post", true],
                ["city", "lead", "city", null, "post", true],
                ["state", "lead", "state", null, "post", true],
                ["zip", "lead", "zip5", null, "post", true],
                ["accident_state", "lead", "accident_state", null, "post", true],
                ["ip_address", "lead", "ip_address", null, "post", true],
                ["source_url", "lead", "source_url", null, "post", true],
                ["dob", "lead", "dob", null, "post", true],
                ["date_injured", "lead", "accident_date_yyyy_mm_dd", null, "post", true],
                ["injury_occured", "lead", "injured", null, "post", true],
                ["hospitalized_or_treated", "lead", "doctor_treatment", null, "post", true],
                ["person_at_fault", "lead", "fault", null, "post", true],
                ["currently_represented", "lead", "attorney", null, "post", true],
                ["ping_id", "computed", "ping_id", null, "post", false],
            ],
        ];

        foreach ($buyers as $code => $data) {
            $buyer = Buyer::updateOrCreate(
                ["code" => $code],
                [
                    "name" => $data["name"],
                    "type" => $data["type"],
                    "scope" => $data["type"] === "rtb"
                        ? "rtb"
                        : (in_array($code, ["D1","D2","D6","D25","D26","D27","D30","D32"], true) ? "unified" : "single"),
                    "payload_format" => "form",
                    "platform" => in_array($code, ["D32","D26"], true) ? "trackdrive" : (in_array($code, ["D23"], true) ? "ringba" : (in_array($code, ["D1","D25","D30"], true) ? "leadspedia" : "custom")),
                    "default_product_id" => $product?->id,
                    "default_campaign_id" => $campaign?->id,
                    "active" => true,
                ]
            );

            if ($code === "D1") {
                $buyer->update([
                    "post_url" => "https://growmyfirmonline.leadspediatrack.com/post.do",
                    "payload_format" => "form",
                ]);
            } elseif ($code === "D2") {
                $buyer->update([
                    "post_url" => "https://api.accident.com/api/lead-create",
                    "payload_format" => "json",
                    "headers_json" => [
                        "api-key: UuVb26nX-IPCa-kEOT-uW6a-L6DfTVOvyx2Z",
                        "api-secret: 1a11d1621d4476240c21ad0bd92e2972da21117e",
                    ],
                ]);
            } elseif ($code === "D6") {
                $buyer->update([
                    "post_url" => "https://app.leadconduit.com/flows/661eeb850ebe9b2e4e22ca05/sources/681e2bfa616414d18349203e/submit",
                    "payload_format" => "form",
                ]);
            } elseif ($code === "D23") {
                $buyer->update([
                    "post_url" => "https://rtb.ringba.com/v1/production/dece46cdd8064609a5dfec17da7cb010.json",
                    "payload_format" => "json",
                ]);
            } elseif ($code === "D25") {
                $buyer->update([
                    "post_url" => "https://trueblue.leadspediatrack.com/call-preping.do",
                    "payload_format" => "form",
                ]);
            } elseif ($code === "D26") {
                $buyer->update([
                    "post_url" => "https://horizons-law-consultants.trackdrive.com/api/v1/leads",
                    "payload_format" => "form",
                ]);
            } elseif ($code === "D27") {
                $buyer->update([
                    "post_url" => "https://horizonswebform.com/pingpost.php",
                    "payload_format" => "json",
                ]);
            } elseif ($code === "D30") {
                $buyer->update([
                    "post_url" => "https://growmyfirmonline.leadspediatrack.com/post.do",
                    "payload_format" => "form",
                ]);
            } elseif ($code === "D32") {
                $buyer->update([
                    "ping_url" => "https://infoworx.trackdrive.com/api/v1/inbound_webhooks/ping/check_for_buyer_availability_on_mva",
                    "post_url" => "https://infoworx.trackdrive.com/api/v1/inbound_webhooks/post/check_for_buyer_availability_on_mva",
                    "payload_format" => "form",
                ]);
            }

            if (array_key_exists($code, $fieldMap)) {
                foreach ($fieldMap[$code] as $item) {
                    [$fieldName, $sourceType, $sourceKey, $sourceValue, $direction, $isRequired] = $item;
                    BuyerField::firstOrCreate([
                        "buyer_id" => $buyer->id,
                        "field_name" => $fieldName,
                        "direction" => $direction,
                        "source_type" => $sourceType,
                        "source_key" => $sourceKey,
                        "source_value" => $sourceValue,
                    ], [
                        "required" => $isRequired,
                    ]);
                }
                continue;
            }

            if (array_key_exists($code, $required)) {
                foreach ($required[$code] as $field) {
                    BuyerField::firstOrCreate([
                        "buyer_id" => $buyer->id,
                        "field_name" => $field,
                        "direction" => "single",
                        "source_type" => "lead",
                    ], [
                        "source_key" => $field,
                        "required" => true,
                    ]);
                }
            }
        }
    }
}
