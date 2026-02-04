<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadField extends Model
{
    use HasFactory;

    protected $fillable = [
        "key",
        "label",
        "type",
        "options",
        "active",
    ];

    protected $casts = [
        "options" => "array",
        "active" => "boolean",
    ];
}
