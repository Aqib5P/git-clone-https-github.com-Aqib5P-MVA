<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuyerField extends Model
{
    use HasFactory;

    protected $fillable = [
        "buyer_id",
        "direction",
        "field_name",
        "source_type",
        "source_key",
        "source_value",
        "required",
    ];

    protected $casts = [
        "required" => "boolean",
    ];

    public function buyer()
    {
        return $this->belongsTo(Buyer::class);
    }
}
