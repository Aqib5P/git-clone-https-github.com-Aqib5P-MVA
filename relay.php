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

// Google Sheets logging endpoint (Apps Script Web App)
$LOG_ENDPOINT = "https://script.google.com/macros/s/AKfycbxfkW7ltasn3JKUb63PBVcp2ZQCAUe7JzV8uKvcq73RKhV1CF6jfoNclTZaCRdSj_cyEw/exec";

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

function curl_post($url, $body, $headers = [], $is_json = false, $timeout = 30) {
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
    CURLOPT_TIMEOUT => $timeout,
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

function log_to_sheet($endpoint, $lead, $payload, $result) {
  global $LOG_ENDPOINT;
  if (!$LOG_ENDPOINT) return;

  $body = [
    "endpoint" => $endpoint,
    "lead" => $lead,
    "payload" => $payload,
    "response" => $result,
    "upstream_status" => val($result, "status", ""),
    "effective_ok" => val($result, "effective_ok", ""),
  ];

  // Best-effort logging. Do not block main response if logging fails.
  curl_post($LOG_ENDPOINT, $body, [], true, 8);
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
$address    = trim(val($d, "address", ""));
$city       = trim(val($d, "city", ""));
$state      = trim(val($d, "state", ""));
$acc_state  = trim(val($d, "accident_state", ""));
$source_url = trim(val($d, "source_url", ""));
$ip_address = trim(val($d, "ip_address", ""));
$dob        = trim(val($d, "dob", ""));
$acc_date_yyyy_mm_dd = trim(val($d, "accident_date_yyyy_mm_dd", ""));
$acc_date_mmddyyyy   = trim(val($d, "accident_date_mmddyyyy", ""));
if (!$acc_date_mmddyyyy && $acc_date_yyyy_mm_dd) {
  $acc_date_mmddyyyy = mmddyyyy_from_date_input($acc_date_yyyy_mm_dd);
}
$cert_id = trim(val($d, "cert_id", ""));
$cert_url = trim(val($d, "cert_url", ""));
$cert_type = trim(val($d, "cert_type", ""));
$incident_date_option_b = val($d, "incident_date_option_b", "");
$attorney = val($d, "attorney", "");
$fault = val($d, "fault", "");
$injured = val($d, "injured", "");
$doctor_treatment = val($d, "doctor_treatment", "");
$role_in_accident = val($d, "role_in_accident", "");

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
    log_to_sheet("D1", $d, $payload, $result);
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
      "source_url" => $source_url,
      "ip_address" => $ip_address,
      "fields" => build_d2_fields($d),
    ];

    $result = finalize_upstream(curl_post($url, $payload, $headers, true));
    log_to_sheet("D2", $d, $payload, $result);
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
    log_to_sheet("D6", $d, $payload, $result);
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
    log_to_sheet("D23", $d, $payload, $result);
    json_out(["endpoint" => "D23", "upstream" => $result]);
  }

  case "D25": {
    $url = "https://trueblue.leadspediatrack.com/call-preping.do";
    $payload = [
      "lp_campaign_id" => "6937514e0c245",
      "lp_campaign_key" => "BRLfV3z4Z9Hc7TgkWyKF",
      "first_name" => $first_name,
      "last_name" => $last_name,
      "zip_code" => $zip5,
      "city" => $city,
      "phone_home" => $phone,
      "email_address" => $email,
      "trusted_form_cert_id" => $cert_id,
      "ip_address" => $ip_address,
      "attorney" => $attorney,
      "fault" => $fault,
      "injured" => $injured,
      "doctor_treatment" => $doctor_treatment,
      "incident_date" => $incident_date_option_b,
      "lp_caller_id" => $phone,
    ];

    $result = finalize_upstream(curl_post($url, $payload, [], false));
    log_to_sheet("D25", $d, $payload, $result);
    json_out(["endpoint" => "D25", "upstream" => $result]);
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
    log_to_sheet("D26", $d, $payload, $result);
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
    log_to_sheet("D27", $d, $payload, $result);
    json_out(["endpoint" => "D27", "upstream" => $result]);
  }

  case "D30": {
    $url = "https://growmyfirmonline.leadspediatrack.com/post.do";
    $payload = [
      "lp_campaign_id" => "6973a18b79a75",
      "lp_campaign_key" => "Rv8w4LDHXWd63thxk2pn",
      "first_name" => $first_name,
      "last_name" => $last_name,
      "email_address" => $email,
      "zip_code" => $zip5,
      "phone_home" => $phone,
      "trusted_form_cert_id" => $cert_id,
      "ip_address" => $ip_address,
      "lp_caller_id" => $phone,
    ];

    $result = finalize_upstream(curl_post($url, $payload, [], false));
    log_to_sheet("D30", $d, $payload, $result);
    json_out(["endpoint" => "D30", "upstream" => $result]);
  }

  case "D32": {
    $ping_url = "https://infoworx.trackdrive.com/api/v1/inbound_webhooks/ping/check_for_buyer_availability_on_mva";
    $post_url = "https://infoworx.trackdrive.com/api/v1/inbound_webhooks/post/check_for_buyer_availability_on_mva";

    $ping_payload = [
      "trackdrive_number" => "+18446757519",
      "traffic_source_id" => "7785",
      "buyer_td_traffic_source_id" => "7785",
      "has_insurance" => "Yes",
      "cited" => "No",
      "settlement" => "No",
      "needs_attorney" => "Yes",
      "claimant_relationship" => "SELF",
      "caller_id" => $phone,
      "trusted_form_token" => $cert_id,
      "trusted_form_cert_url" => $cert_url,
      "cert_id" => $cert_id,
      "cert_type" => $cert_type,
      "first_name" => $first_name,
      "last_name" => $last_name,
      "email" => $email,
      "address" => $address,
      "city" => $city,
      "state" => $state,
      "zip" => $zip5,
      "accident_state" => $acc_state,
      "date_injured" => $acc_date_yyyy_mm_dd,
      "injury_occured" => $injured,
      "hospitalized_or_treated" => $doctor_treatment,
      "person_at_fault" => $fault,
      "currently_represented" => $attorney,
      "incident_position" => $role_in_accident,
    ];

    $ping_result = finalize_upstream(curl_post($ping_url, $ping_payload, [], false));
    $ping_id = "";
    if (isset($ping_result["body_json"]) && is_array($ping_result["body_json"])) {
      $body_json = $ping_result["body_json"];
      if (isset($body_json["ping_id"])) $ping_id = $body_json["ping_id"];
      elseif (isset($body_json["pingId"])) $ping_id = $body_json["pingId"];
      elseif (isset($body_json["id"])) $ping_id = $body_json["id"];
      elseif (isset($body_json["try_all_buyers_ping_id"])) $ping_id = $body_json["try_all_buyers_ping_id"];
      elseif (isset($body_json["try_all_buyers"]) && is_array($body_json["try_all_buyers"]) && isset($body_json["try_all_buyers"]["ping_id"])) {
        $ping_id = $body_json["try_all_buyers"]["ping_id"];
      } elseif (isset($body_json["buyers"]) && is_array($body_json["buyers"]) && isset($body_json["buyers"][0]) && is_array($body_json["buyers"][0]) && isset($body_json["buyers"][0]["ping_id"])) {
        $ping_id = $body_json["buyers"][0]["ping_id"];
      }
    }

    $post_payload = [
      "trackdrive_number" => "+18446757519",
      "traffic_source_id" => "7785",
      "has_insurance" => "Yes",
      "trusted_form_token" => $cert_id,
      "trusted_form_cert_url" => $cert_url,
      "first_name" => $first_name,
      "last_name" => $last_name,
      "email" => $email,
      "address" => $address,
      "city" => $city,
      "state" => $state,
      "zip" => $zip5,
      "accident_state" => $acc_state,
      "ip_address" => $ip_address,
      "source_url" => $source_url,
      "dob" => $dob,
      "date_injured" => $acc_date_yyyy_mm_dd,
      "injury_occured" => $injured,
      "hospitalized_or_treated" => $doctor_treatment,
      "person_at_fault" => $fault,
      "currently_represented" => $attorney,
      "ping_id" => $ping_id,
    ];

    $post_attempted = false;
    $post_skipped_reason = "";
    $post_result = null;
    if ($ping_id && val($ping_result, "effective_ok", false)) {
      $post_attempted = true;
      $post_result = finalize_upstream(curl_post($post_url, $post_payload, [], false));
    } elseif (!$ping_id) {
      $post_skipped_reason = "Missing ping_id in ping response";
    } else {
      $post_skipped_reason = "Ping not accepted";
    }

    $log_payload = ["ping" => $ping_payload];
    if ($post_attempted) $log_payload["post"] = $post_payload;

    $log_result = ["ping" => $ping_result, "post_attempted" => $post_attempted];
    $status = val($ping_result, "status", "");
    $effective_ok = val($ping_result, "effective_ok", false);
    if ($post_result) {
      $log_result["post"] = $post_result;
      $status = val($post_result, "status", "");
      $effective_ok = val($post_result, "effective_ok", false);
    }
    $log_result["status"] = $status;
    $log_result["effective_ok"] = $effective_ok;
    if ($post_skipped_reason) $log_result["post_skipped_reason"] = $post_skipped_reason;

    log_to_sheet("D32", $d, $log_payload, $log_result);

    if ($post_result) {
      json_out(["endpoint" => "D32", "upstream" => $post_result, "ping" => $ping_result, "post" => $post_result]);
    }

    json_out([
      "endpoint" => "D32",
      "upstream" => $ping_result,
      "ping" => $ping_result,
      "post_attempted" => $post_attempted,
      "post_skipped_reason" => $post_skipped_reason,
    ]);
  }

  default:
    json_out(["error" => "Unknown endpoint. Allowed: D1, D2, D6, D23, D25, D26, D27, D30, D32"], 400);
}

