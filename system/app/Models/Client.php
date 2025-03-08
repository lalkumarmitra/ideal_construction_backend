<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    public const TYPE_LOADING_POINT = 'loading_point';
    public const TYPE_UNLOADING_POINT = 'unloading_point';
    public const TYPE_LIST = [
        self::TYPE_LOADING_POINT,
        self::TYPE_UNLOADING_POINT
    ];
    public const SIZE_SMALL = 'small';
    public const SIZE_MEDIUM = 'medium';
    public const SIZE_LARGE = 'big';
    public const SIZE_MISC = 'misc';
    public const SIZE_LIST = [
        self::SIZE_SMALL,
        self::SIZE_MEDIUM,
        self::SIZE_LARGE,
        self::SIZE_MISC
    ];
    protected $fillable = [
        "name",
        "image",
        "client_size",
        "type",
        "address",
        "state",
        "pin",
        "frequency_of_use",
    ];
    public function transactions():HasMany {
        return $this->hasMany(Transaction::class,'loading_point_id','id');
    }
}
