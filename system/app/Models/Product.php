<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [ "name", "rate", "unit", "image", "description","frequency_of_use" ];

    public function transactions():HasMany {
        return $this->hasMany(Transaction::class,'product_id','id');
    }
    
}
