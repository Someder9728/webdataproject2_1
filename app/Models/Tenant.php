<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        't_Fname',
        't_Lname',
        't_tel',
        't_mail',
        't_address',
    ];

    protected $primaryKey = 't_id';

    /** @return HasOne<User, $this> */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'tenants_t_id', 't_id');
    }

    /** @return HasMany<Rental, $this> */
    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class, 'tenants_t_id', 't_id');
    }

    /** @return HasMany<Repair, $this> */
    public function repairs(): HasMany
    {
        return $this->hasMany(Repair::class, 'tenants_t_id', 't_id');
    }
}
