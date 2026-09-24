<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Tenant;
use App\Models\Room;
use App\Models\Contract;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    public function tenant() {
        return $this->belongsTo(Tenant::class, 'tenants_t_id', 't_id');
    }

    public function room() {
        return $this->belongsTo(Room::class, 'rooms_r_id', 'r_id');
    }

    public function contract() {
        return $this->hasOne(Contract::class, 'rentals_rt_id', 'rt_id');
    }

    public function invoices() {
        return $this->hasMany(Invoice::class, 'rentals_rt_id', 'rt_id');
    }
    public function scopeSearch($query, ?string $term)
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
}
