<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property CarbonInterface $rt_movein
 * @property CarbonInterface|null $rt_moveout
 */
class Rental extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'rt_movein',
        'rt_moveout',
        'rt_status',
        'rooms_r_id',
        'tenants_t_id',
    ];

    protected $primaryKey = 'rt_id';

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenants_t_id', 't_id');
    }

    /** @return BelongsTo<Room, $this> */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'rooms_r_id', 'r_id');
    }

    /** @return HasOne<Contract, $this> */
    public function contract(): HasOne
    {
        return $this->hasOne(Contract::class, 'rentals_rt_id', 'rt_id');
    }

    /** @return HasMany<Invoice, $this> */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'rentals_rt_id', 'rt_id');
    }

    /**
     * @param  Builder<Rental>  $query
     * @return Builder<Rental>
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->whereHas('tenant', function ($tq) use ($term) {
                $tq->where('t_Fname', 'like', "%{$term}%")
                    ->orWhere('t_Lname', 'like', "%{$term}%")
                    ->orWhere('t_tel', 'like', "%{$term}%");
            })->orWhereHas('room', function ($rq) use ($term) {
                $rq->where('r_name', 'like', "%{$term}%");
            });
        });
    }

    protected function casts(): array
    {
        return [
            'rt_movein' => 'date',
            'rt_moveout' => 'date',
        ];
    }
}
