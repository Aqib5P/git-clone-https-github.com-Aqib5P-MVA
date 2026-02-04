<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attempt extends Model
{
    use HasFactory;

    protected $fillable = [
        "lead_id",
        "buyer_id",
        "endpoint",
        "direction",
        "status",
        "http_status",
        "ping_id",
        "forwarding_number",
        "payout",
        "bid_amount",
        "payload_json",
        "response_json",
        "response_raw",
    ];

    protected $casts = [
        "payload_json" => "array",
        "response_json" => "array",
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function buyer()
    {
        return $this->belongsTo(Buyer::class);
    }
}
