<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        "first_name",
        "last_name",
        "email",
        "phone",
        "zip5",
        "city",
        "state",
        "accident_state",
        "ip_address",
        "source_url",
        "cert_id",
        "cert_url",
        "lead_json",
    ];

    protected $casts = [
        "lead_json" => "array",
    ];

    public function attempts()
    {
        return $this->hasMany(Attempt::class);
    }
}
