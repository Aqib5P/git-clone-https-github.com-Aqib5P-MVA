<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Buyer extends Model
{
    use HasFactory;

    protected $fillable = [
        "code",
        "name",
        "type",
        "scope",
        "payload_format",
        "platform",
        "ping_url",
        "post_url",
        "headers_json",
        "response_rules",
        "public_token",
        "public_enabled",
        "default_product_id",
        "default_campaign_id",
        "default_publisher_id",
        "static_payout",
        "payout_type",
        "payout_model",
        "payment_terms",
        "active",
        "notes",
        "priority",
    ];

    protected $casts = [
        "headers_json" => "array",
        "response_rules" => "array",
        "public_enabled" => "boolean",
        "active" => "boolean",
        "priority" => "integer",
    ];

    public function fields()
    {
        return $this->hasMany(BuyerField::class);
    }
}
