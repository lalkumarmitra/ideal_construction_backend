<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'gender',
        'dob',
        'email',
        'phone',
        'password',
        'avatar',
        'role_id',
        'is_active',
        'is_blocked'
    ];
    
    protected $casts = [
        'password' => 'hashed', 
        'dob' => 'date',
    ];
    protected $hidden = ['password'];
    public function role(): BelongsTo {
        return $this->belongsTo(Role::class);
    }
    public function unLoadedTransactions() : HasMany {
        return $this->hasMany(Transaction::class,'unloading_driver_id','id');
    }
    public function loadedTransactions() : HasMany {
        return $this->hasMany(Transaction::class,'loading_driver_id','id');
    }
}

