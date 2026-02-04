<?php

namespace Database\Seeders;

use App\Models\Buyer;
use App\Models\BuyerField;
use Illuminate\Database\Seeder;

class BuyerSeeder extends Seeder
{
    public function run(): void
    {
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

        foreach ($buyers as $code => $data) {
            $buyer = Buyer::updateOrCreate(
                ["code" => $code],
                [
                    "name" => $data["name"],
                    "type" => $data["type"],
                    "active" => true,
                ]
            );

            if (!array_key_exists($code, $required)) {
                continue;
            }

            foreach ($required[$code] as $field) {
                BuyerField::firstOrCreate([
                    "buyer_id" => $buyer->id,
                    "field_name" => $field,
                    "direction" => "single",
                    "source_type" => "lead",
                ], [
                    "required" => true,
                ]);
            }
        }
    }
}
