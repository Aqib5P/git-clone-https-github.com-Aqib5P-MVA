<?php

namespace App\Services;

class GoogleLogger
{
    public function log(array $payload): void
    {
        $endpoint = config("admin.google_log_endpoint");
        if (!$endpoint) return;

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }
}
