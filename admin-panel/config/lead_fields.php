<?php

return [
    "first_name" => ["label" => "First Name", "type" => "text"],
    "last_name" => ["label" => "Last Name", "type" => "text"],
    "email" => ["label" => "Email", "type" => "email"],
    "phone" => ["label" => "Phone", "type" => "tel"],
    "zip5" => ["label" => "ZIP", "type" => "text"],
    "address" => ["label" => "Address", "type" => "text"],
    "city" => ["label" => "City", "type" => "text"],
    "state" => ["label" => "State", "type" => "text"],
    "accident_state" => ["label" => "Accident State", "type" => "text"],
    "accident_date_raw" => ["label" => "Accident Date", "type" => "date"],
    "accident_date_mmddyyyy" => ["label" => "Accident Date (MM/DD/YYYY)", "type" => "text"],
    "accident_date_yyyy_mm_dd" => ["label" => "Accident Date (YYYY-MM-DD)", "type" => "text"],
    "dob" => ["label" => "Date of Birth", "type" => "date"],
    "ip_address" => ["label" => "IP Address", "type" => "text"],
    "source_url" => ["label" => "Source URL", "type" => "url"],
    "accident_sol" => [
        "label" => "Accident SOL",
        "type" => "select",
        "options" => ["1" => "Within 1 Year", "2" => "Within 2 Years"],
    ],
    "incident_date_option_b" => [
        "label" => "When did the accident happen?",
        "type" => "select",
        "options" => [
            "Less than 1 year" => "Less than 1 year",
            "Less than 2 years" => "Less than 2 years",
            "Over 2 years" => "Over 2 years",
        ],
    ],
    "injury_cause" => [
        "label" => "What caused your injury?",
        "type" => "select",
        "options" => [
            "Motorcycle Accident" => "Motorcycle Accident",
            "Car Accident" => "Car Accident",
            "Truck Accident" => "Truck Accident",
            "Slip and Fall" => "Slip and Fall",
            "Other" => "Other",
        ],
    ],
    "primary_injury" => [
        "label" => "Did you sustain any of the following?",
        "type" => "select",
        "options" => [
            "Headaches" => "Headaches",
            "Whiplash" => "Whiplash",
            "Broken Bones" => "Broken Bones",
            "Back Injury" => "Back Injury",
            "Other" => "Other",
        ],
    ],
    "role_in_accident" => [
        "label" => "Role in accident",
        "type" => "select",
        "options" => [
            "Driver" => "Driver",
            "Passenger" => "Passenger",
            "Pedestrian" => "Pedestrian",
        ],
    ],
    "attorney" => [
        "label" => "Already Represented",
        "type" => "select",
        "options" => ["No" => "No", "Yes" => "Yes"],
    ],
    "fault" => [
        "label" => "Were You at Fault?",
        "type" => "select",
        "options" => ["No" => "No", "Yes" => "Yes"],
    ],
    "injured" => [
        "label" => "Were You Injured?",
        "type" => "select",
        "options" => ["Yes" => "Yes", "No" => "No"],
    ],
    "doctor_treatment" => [
        "label" => "Doctor Treatment",
        "type" => "select",
        "options" => ["Yes" => "Yes", "No" => "No"],
    ],
    "cert_url" => ["label" => "Certificate URL", "type" => "text"],
    "cert_id" => ["label" => "Certificate ID", "type" => "text"],
    "cert_type" => [
        "label" => "Certificate Type",
        "type" => "select",
        "options" => ["TrustedForm" => "TrustedForm", "Jornaya" => "Jornaya"],
    ],
    "jornaya_leadid" => ["label" => "Jornaya leadid", "type" => "text"],
    "notes" => ["label" => "Notes", "type" => "text"],
];
