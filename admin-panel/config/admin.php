<?php

return [
    "username" => env("ADMIN_USERNAME", "admin"),
    "password" => env("ADMIN_PASSWORD", ""),
    "intake_token" => env("INTAKE_API_TOKEN", ""),
    "google_log_endpoint" => env("GOOGLE_LOG_ENDPOINT", ""),
    "duplicate_window_days" => (int) env("DUPLICATE_WINDOW_DAYS", 30),
    "office_ips" => array_filter(array_map("trim", explode(",", env("OFFICE_IPS", "")))),
];
