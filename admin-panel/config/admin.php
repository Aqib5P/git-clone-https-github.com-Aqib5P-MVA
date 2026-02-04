<?php

return [
    "username" => env("ADMIN_USERNAME", "admin"),
    "password" => env("ADMIN_PASSWORD", ""),
    "intake_token" => env("INTAKE_API_TOKEN", ""),
    "office_ips" => array_filter(array_map("trim", explode(",", env("OFFICE_IPS", "")))),
];
