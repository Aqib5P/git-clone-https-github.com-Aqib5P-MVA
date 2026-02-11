<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Support\Facades\Schema;

class ApplyAppTimezone
{
    public function handle($request, Closure $next)
    {
        if (Schema::hasTable("settings")) {
            $timezone = Setting::where("key", "timezone")->value("value");
            if (is_string($timezone) && $timezone !== "") {
                config(["app.timezone" => $timezone]);
                date_default_timezone_set($timezone);
            }
        }

        return $next($request);
    }
}
