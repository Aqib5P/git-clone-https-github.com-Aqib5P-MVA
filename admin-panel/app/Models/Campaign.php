<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        "product_id",
        "code",
        "name",
        "active",
    ];

    protected $casts = [
        "active" => "boolean",
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
