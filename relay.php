<?php
// relay.php
// Server-side router to keep buyer endpoints + API keys hidden.
// Receives JSON: { "endpoint": "D1"|"D2"|... , "data": { ...unified fields... } }
// Returns JSON with upstream status + body.

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
  http_response_code(200);
  exit;
}

function json_out($arr, $code = 200) {
  http_response_code($code);
  echo json_encode($arr, JSON_PRETTY_PRINT);
  exit;
}

function get_json_input() {
  $raw = file_get_contents("php://input");
  if (!$raw) return null;
  $decoded = json_decode($raw, true);
  return is_array($decoded) ? $decoded : null;
}

function val($arr, $key, $default = "") {
  if (!is_array($arr)) return $default;
  return isset($arr[$key]) ? $arr[$key] : $default;
}

function mmddyyyy_from_date_input($yyyy_mm_dd) {
  if (!$yyyy_mm_dd) return "";
  $dt = DateTime::createFromFormat("Y-m-d", $yyyy_mm_dd);
  return $dt ? $dt->format("m/d/Y") : "";
}

function build_d2_fields($d) {
  $visibleFields = [
    [
      "ref" => "incident_date_option_b",
      "title" => "When did the accident happen?",
      "answer" => val($d, "incident_date_option_b", "")
    ],
    [
      "ref" => "injury_cause",
      "title" => "What caused your injury?",
      "answer" => val($d, "injury_cause", "")
    ],
    [
      "ref" => "primary_injury",
      "title" => "Did you sustain any of the following?",
      "answer" => val($d, "primary_injury", "")
    ],
    [
      "ref" => "role_in_accident",
      "title" => "Were you the driver, passenger or pedestrian?",
      "answer" => val($d, "role_in_accident", "")
    ],
  ];

  $staticFields = [
    [ "ref" => "were_you_injured", "title" => "Were you injured in an Auto Accident?", "answer" => "Yes" ],
    [ "ref" => "were_you_at_fault", "title" => "Were you placed at fault for the accident?", "answer" => "No" ],
    [ "ref" => "expressed_interest", "title" => "Do you want to speak to an Attorney?", "answer" => "Yes" ],
    [ "ref" => "have_attorney", "title" => "Do you have an attorney?", "answer" => "No" ],
    [
      "ref" => "doctor_treatment",
      "title" => "Did the injury require hospitalization, medical treatment, surgery or cause you to miss work?",
      "answer" => "Yes"
    ],
    [ "ref" => "accident_vehicle_count", "title" => "How many cars were involved in the accident?", "answer" => "2" ],
    [ "ref" => "settled_insurance", "title" => "Have you settled with the insurance company regarding your injuries?", "answer" => "No" ],
    [ "ref" => "signed_retainer", "title" => "Have you ever signed a retainer with a law firm regarding this case?", "answer" => "No" ],
    [ "ref" => "driver_insurance", "title" => "Did the other driver, who was at fault, have auto insurance?", "answer" => "Yes" ],
  ];

  return array_merge($visibleFields, $staticFields);
}

function curl_post($url, $body, $headers = [], $is_json = false) {
  $ch = curl_init($url);

  $final_headers = $headers;
  if ($is_json) {
    $final_headers[] = "Content-Type: application/json";
  } else {
    $final_headers[] = "Content-Type: application/x-www-form-urlencoded";
  }

  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => false, // do NOT return upstream headers (reduces leakage)
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $is_json ? json_encode($body) : http_build_query($body),
    CURLOPT_HTTPHEADER => $final_headers,
    CURLOPT_TIMEOUT => 30,
  ]);

  $raw_body = curl_exec($ch);
  if ($raw_body === false) {
    $err = curl_error($ch);
    curl_close($ch);
    return ["ok" => false, "error" => $err];
  }

  $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  return [
    "ok" => ($status >= 200 && $status < 300),
    "status" => $status,
    "body" => $raw_body,
  ];
}

function finalize_upstream($result) {
  $out = $result;
  $out["effective_ok"] = isset($result["ok"]) ? (bool)$result["ok"] : false;
  $body = isset($result["body"]) ? $result["body"] : "";

  if (is_string($body) && $body !== "") {
    $decoded = json_decode($body, true);
    if (is_array($decoded)) {
      $out["body_json"] = $decoded;
      // Common pattern (LeadConduit): {"outcome":"failure", ...}
      if (isset($decoded["outcome"]) && $decoded["outcome"] === "failure") {
        $out["effective_ok"] = false;
      }
    }
  }
  return $out;
}

$input = get_json_input();
if (!$input) {
  json_out(["error" => "Invalid JSON input. Expected { endpoint, data }"], 400);
}

$endpoint = val($input, "endpoint", "");
$d = val($input, "data", []);
if (!is_array($d)) $d = [];

// Common unified values (do not change outbound key names later)
$first_name = trim(val($d, "first_name", ""));
$last_name  = trim(val($d, "last_name", ""));
$email      = trim(val($d, "email", ""));
$phone      = trim(val($d, "phone", ""));
$zip5       = trim(val($d, "zip5", ""));
$state      = trim(val($d, "state", ""));
$acc_state  = trim(val($d, "accident_state", ""));
$acc_date_yyyy_mm_dd = trim(val($d, "accident_date_yyyy_mm_dd", ""));
$acc_date_mmddyyyy   = trim(val($d, "accident_date_mmddyyyy", ""));
if (!$acc_date_mmddyyyy && $acc_date_yyyy_mm_dd) {
  $acc_date_mmddyyyy = mmddyyyy_from_date_input($acc_date_yyyy_mm_dd);
}
$cert_id = trim(val($d, "cert_id", ""));
$cert_url = trim(val($d, "cert_url", ""));

// Route table: keep URLs + secrets here only (NOT in HTML).
switch ($endpoint) {
  case "D1": {
    $url = "https://growmyfirmonline.leadspediatrack.com/post.do";
    $payload = [
      "lp_campaign_id" => "64c953e483f75",
      "lp_campaign_key" => "NxjrqXwd9cZgKBPLHhGD",
      "first_name" => $first_name,
      "last_name" => $last_name,
      "zip_code" => $zip5,
      "phone_home" => $phone,
      "trusted_form_cert_id" => $cert_id,
      "lp_caller_id" => $phone,
    ];

    $result = finalize_upstream(curl_post($url, $payload, [], false));
    json_out(["endpoint" => "D1", "upstream" => $result]);
  }

  case "D2": {
    $url = "https://api.accident.com/api/lead-create";
    $headers = [
      "api-key: UuVb26nX-IPCa-kEOT-uW6a-L6DfTVOvyx2Z",
      "api-secret: 1a11d1621d4476240c21ad0bd92e2972da21117e",
    ];

    $payload = [
      "arrived_at" => gmdate("c"),
      "test_mode" => "false",
      "deal" => "4naA7Klbd3QD25QzJMxe8oZXBVPvgy",
      "lead_first_name" => $first_name,
      "lead_last_name" => $last_name,
      "lead_phone" => $phone,
      "case_type" => "Auto Accident",
      "zip_code" => $zip5,
      "certificate_type" => val($d, "certificate_type", "TrustedForm"),
      "certificate_id" => $cert_id,
      "certificate_url" => $cert_url,
      "source_url" => trim(val($d, "source_url", "")),
      "ip_address" => trim(val($d, "ip_address", "")),
      "fields" => build_d2_fields($d),
    ];

    $result = finalize_upstream(curl_post($url, $payload, $headers, true));
    json_out(["endpoint" => "D2", "upstream" => $result]);
  }

  case "D6": {
    $url = "https://app.leadconduit.com/flows/661eeb850ebe9b2e4e22ca05/sources/681e2bfa616414d18349203e/submit";
    $payload = [
      "phone_1" => $phone,
      "trustedform_cert_url" => $cert_url,
      "first_name" => $first_name,
      "last_name" => $last_name,
      "email" => $email,
      "state_of_accident_qmark" => $state,
      "date_of_accident_qmark" => $acc_date_mmddyyyy,
    ];

    $result = finalize_upstream(curl_post($url, $payload, [], false));
    json_out(["endpoint" => "D6", "upstream" => $result]);
  }

  case "D23": {
    $url = "https://rtb.ringba.com/v1/production/dece46cdd8064609a5dfec17da7cb010.json";
    $payload = [
      "CID" => $phone,
      "exposeCallerId" => "yes",
      "zipcode" => $zip5,
      "State" => $state,
      "SubID" => "hzn345",
      "email" => $email,
      "first_name" => $first_name,
      "last_name" => $last_name,
      "Cert_Type" => "TrustedForm",
      "Cert_Id" => $cert_id,
      "trusted_form_url" => $cert_url,
      "call_type" => "o",
    ];

    $result = finalize_upstream(curl_post($url, $payload, [], true));
    json_out(["endpoint" => "D23", "upstream" => $result]);
  }

  case "D26": {
    $url = "https://horizons-law-consultants.trackdrive.com/api/v1/leads";
    $jornaya = trim(val($d, "jornaya_leadid", "")) ?: $cert_id;
    $payload = [
      "lead_token" => "c5af0485a9a44f8c8832bbc80ea0f618",
      "traffic_source_id" => "1002",
      "caller_id" => $phone,
      "first_name" => $first_name,
      "last_name" => $last_name,
      "email" => $email,
      "zip" => $zip5,
      "trusted_form_cert_url" => $cert_url,
      "jornaya_leadid" => $jornaya,
      "accident_date" => $acc_date_mmddyyyy,
    ];

    $result = finalize_upstream(curl_post($url, $payload, [], false));
    json_out(["endpoint" => "D26", "upstream" => $result]);
  }

  case "D27": {
    $url = "https://horizonswebform.com/pingpost.php";
    $payload = [
      "first_name" => $first_name,
      "last_name" => $last_name,
      "email" => $email,
      "caller_id" => $phone,
      "state" => $state,
      "accident_state" => $acc_state,
      "zip" => $zip5,
      "accident_date" => $acc_date_yyyy_mm_dd, // your PHP expects YYYY-MM-DD and converts internally
      "accident_sol" => val($d, "accident_sol", ""),
      "source_url" => trim(val($d, "source_url", "")),
      "ip_address" => trim(val($d, "ip_address", "")),
      "trusted_form_cert_url" => $cert_url,
      // Hidden static fields
      "have_attorney" => "No",
      "injury_occured" => "Yes",
      "cited" => "No",
      "injury_type" => "Other",
      "hospitalized_or_treated" => "Yes",
    ];

    $result = finalize_upstream(curl_post($url, $payload, [], true));
    json_out(["endpoint" => "D27", "upstream" => $result]);
  }

  default:
    json_out(["error" => "Unknown endpoint. Allowed: D1, D2, D6, D23, D26, D27"], 400);
}

