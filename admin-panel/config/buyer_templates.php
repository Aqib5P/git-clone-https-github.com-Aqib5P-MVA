<?php

return [
    "trackdrive_ping_post" => [
        "label" => "TrackDrive (Ping/Post)",
        "fields" => [
            ["trackdrive_number", "static", null, "", "ping", false],
            ["traffic_source_id", "static", null, "", "ping", false],
            ["caller_id", "lead", "phone", null, "ping", true],
            ["zip", "lead", "zip5", null, "ping", true],
            ["state", "lead", "state", null, "ping", true],
            ["first_name", "lead", "first_name", null, "ping", true],
            ["last_name", "lead", "last_name", null, "ping", true],
            ["email", "lead", "email", null, "ping", true],
            ["trusted_form_cert_url", "lead", "cert_url", null, "ping", true],

            ["trackdrive_number", "static", null, "", "post", false],
            ["traffic_source_id", "static", null, "", "post", false],
            ["caller_id", "lead", "phone", null, "post", true],
            ["zip", "lead", "zip5", null, "post", true],
            ["state", "lead", "state", null, "post", true],
            ["first_name", "lead", "first_name", null, "post", true],
            ["last_name", "lead", "last_name", null, "post", true],
            ["email", "lead", "email", null, "post", true],
            ["trusted_form_cert_url", "lead", "cert_url", null, "post", true],
            ["ping_id", "computed", "ping_id", null, "post", false],
        ],
    ],
    "ringba_rtb" => [
        "label" => "Ringba RTB",
        "fields" => [
            ["CID", "lead", "phone", null, "single", true],
            ["zipcode", "lead", "zip5", null, "single", true],
            ["exposeCallerId", "static", null, "yes", "single", false],
            ["call_type", "static", null, "o", "single", false],
        ],
    ],
    "leadspedia_form" => [
        "label" => "Leadspedia (Full Post)",
        "fields" => [
            ["lp_campaign_id", "static", null, "", "single", false],
            ["lp_campaign_key", "static", null, "", "single", false],
            ["first_name", "lead", "first_name", null, "single", true],
            ["last_name", "lead", "last_name", null, "single", true],
            ["email_address", "lead", "email", null, "single", true],
            ["zip_code", "lead", "zip5", null, "single", true],
            ["phone_home", "lead", "phone", null, "single", true],
            ["trusted_form_cert_id", "lead", "cert_id", null, "single", true],
            ["lp_caller_id", "lead", "phone", null, "single", false],
        ],
    ],
];
