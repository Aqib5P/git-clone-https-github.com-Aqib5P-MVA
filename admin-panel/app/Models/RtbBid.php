<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RtbBid extends Model
{
    use HasFactory;

    protected $fillable = [
        "attempt_id",
        "buyer_id",
        "bid_id",
        "bid_amount",
        "phone_number",
        "sip_address",
        "expires_at",
        "bid_json",
    ];

    protected $casts = [
        "bid_json" => "array",
        "expires_at" => "datetime",
    ];
}
