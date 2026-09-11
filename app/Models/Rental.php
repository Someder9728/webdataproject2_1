<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Tenant;
use App\Models\Room;
use App\Models\Contract;
use App\Models\Invoice;

class Rental extends Model
{
    protected $fillable = [
    'rt_movein',
    'rt_moveout',
    'rt_status',
    'rooms_r_id',
    'tenants_t_id',
];

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
}
