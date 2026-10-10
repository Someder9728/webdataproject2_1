<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'r_name',
        'r_floor',
        'r_type',
        'r_rent',
        'r_status',
    ];

    protected $primaryKey = 'r_id';

    /** @return HasMany<Rental, $this> */
    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class, 'rooms_r_id', 'r_id');
    }

    /** @return HasMany<Meter, $this> */
    public function meters(): HasMany
    {
        return $this->hasMany(Meter::class, 'rooms_r_id', 'r_id');
    }

    /** @return HasMany<Repair, $this> */
    public function repairs(): HasMany
    {
        return $this->hasMany(Repair::class, 'rooms_r_id', 'r_id');
    }

    protected function casts(): array
    {
        return [
            'r_floor' => 'integer',
            'r_rent' => 'decimal:2',
        ];
    }
}
