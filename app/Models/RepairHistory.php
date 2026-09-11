<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Repair;

class RepairHistory extends Model
{
    protected $fillable = [
    'rph_name',
    'rph_description',
    'rph_status',
    'rph_type',
    'repairs_rp_id',
];

    public function repair()  {
        return $this->belongsTo(Repair::class, 'repairs_rp_id', 'rp_id');
    }
}
