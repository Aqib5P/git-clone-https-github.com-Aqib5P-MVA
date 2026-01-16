# DID Routing Portal

## Overview
- `did_lookup.php` returns an available DID based on the dialed number's area code, with a daily usage limit per DID.
- `/admin` provides a simple UI to add/remove DIDs and manage the IP whitelist.

## Setup
1. Create the database schema:
   - Run `sql/schema.sql` in your MySQL database.
2. Configure environment variables (optional but recommended):
   - `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`, `DB_PORT`
   - `IP_WHITELIST_ENABLED` (`1` or `0`)
   - `ALLOWED_IPS` (comma-separated fallback list if the whitelist table is empty)
   - `DID_DAILY_LIMIT` (default `50`)
   - `DID_RETURN_FORMAT` (`plain` or `json`)
   - `DID_RETURN_E164` (`1` or `0`)
   - `DID_COUNTRY_CODE` (default `1`)
   - `ADMIN_ALLOW_FIRST_USER_SETUP` (`1` or `0`)

## Admin Portal
- Visit `/admin/login.php` to create the first admin user (if none exist).
- Use the DIDs page to add/remove DID numbers.
- Use the IP Whitelist page to allow trusted client IPs.

## DID Lookup API
- GET or POST `did_lookup.php?dialed_number=2125550123`
- Add `format=json` to get a JSON response.

## Notes on Improvements
- The lookup endpoint uses fair ordering instead of random selection to reduce DB load.
- Daily usage is tracked with `did_usage_daily` and protected by a transaction to reduce double-assignments.
- The IP whitelist is stored in the database instead of hard-coded values.
