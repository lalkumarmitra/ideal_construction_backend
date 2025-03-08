<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    protected $fillable = [
        "number",
        "type",
        "frequency_of_use"
    ];
    public function transactions():HasMany {
        return $this->hasMany(Transaction::class,'loading_vehicle_id','id');
    }
}
