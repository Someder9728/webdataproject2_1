<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Repair extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'rp_name',
        'rp_description',
        'rp_status',
        'rp_type',
        'tenants_t_id',
        'rooms_r_id',
        'reported_by_user_id',
    ];

    protected $primaryKey = 'rp_id';

    /**
     * @param  Builder<Repair>  $query
     * @return Builder<Repair>
     */
    public function scopeVisibleTo(Builder $query, User $actor): Builder
    {
        if ($actor->u_role === 'admin') {
            return $query;
        }

        return $query->where(function ($query) use ($actor) {
            $query->where('reported_by_user_id', $actor->getKey());
            if ($actor->tenants_t_id !== null) {
                $query->orWhere('tenants_t_id', $actor->tenants_t_id);
            }
        });
    }

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

    /** @return HasMany<RepairHistory, $this> */
    public function histories(): HasMany
    {
        return $this->hasMany(RepairHistory::class, 'repairs_rp_id', 'rp_id');
    }

    /** @return BelongsTo<User, $this> */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id', 'u_id');
    }
}
