<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    public const TYPE_ADMIN = 'admin';
    public const TYPE_STAFF = 'staff';
    public const TYPE_MANAGER = 'manager';
    public const TYPE_DRIVER = 'driver';
    public const TYPE_CLIENT = 'client';
    public const TYPE_CUSTOMER = 'customer';
    public const TYPE_LIST = [
        self::TYPE_ADMIN,
        self::TYPE_STAFF,
        self::TYPE_MANAGER,
        self::TYPE_DRIVER,
        self::TYPE_CLIENT,
        self::TYPE_CUSTOMER
    ];
    protected $fillable = ['name', 'type', 'priority'];
    protected $hidden = ['created_at', 'updated_at'];
    public function users() {
        return $this->hasMany(User::class);
    }
}
