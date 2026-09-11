<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Tenant;
use App\Models\Room;
use App\Models\RepairHistory;


class Repair extends Model
{
    protected $fillable = [
    'rp_name',
    'rp_description',
    'rp_status',
    'rp_type',
    'tenants_t_id',
    'rooms_r_id',
];

    public function tenant() {
        return $this->belongsTo(Tenant::class, 'tenants_t_id', 't_id');
    }

    public function room() {
        return $this->belongsTo(Room::class, 'rooms_r_id', 'r_id');
    }

    public function histories() {
        return $this->hasMany(RepairHistory::class, 'repairs_rp_id', 'rp_id');
    }
}
