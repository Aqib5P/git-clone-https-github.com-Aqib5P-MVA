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
        "ping_url",
        "post_url",
        "headers_json",
        "public_token",
        "public_enabled",
        "active",
        "notes",
    ];

    protected $casts = [
        "headers_json" => "array",
        "public_enabled" => "boolean",
        "active" => "boolean",
    ];

    public function fields()
    {
        return $this->hasMany(BuyerField::class);
    }
}
