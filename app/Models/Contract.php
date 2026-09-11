<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Rental;


class Contract extends Model
{
    protected $fillable = [
    'c_number',
    'c_start',
    'c_end',
    'c_rent',
    'c_deposit',
    'c_status',
    'rentals_rt_id',
];

    public function rental()
    {
        return $this->belongsTo(Rental::class, 'rentals_rt_id', 'rt_id');
    }
}
