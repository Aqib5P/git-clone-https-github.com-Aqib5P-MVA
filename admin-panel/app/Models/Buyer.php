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
        "active",
        "notes",
    ];

    public function fields()
    {
        return $this->hasMany(BuyerField::class);
    }
}
