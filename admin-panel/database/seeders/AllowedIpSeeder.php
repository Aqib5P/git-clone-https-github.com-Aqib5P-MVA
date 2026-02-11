<?php

namespace Database\Seeders;

use App\Models\AllowedIp;
use Illuminate\Database\Seeder;

class AllowedIpSeeder extends Seeder
{
    public function run(): void
    {
        $ips = config("admin.office_ips");
        if (!is_array($ips)) {
            return;
        }

        foreach ($ips as $ip) {
            if (!$ip) continue;
            AllowedIp::firstOrCreate(["ip" => $ip], ["label" => "Office IP"]);
        }
    }
}
