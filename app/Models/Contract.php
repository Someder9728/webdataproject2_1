<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Rental;
use Illuminate\Database\Eloquent\SoftDeletes;


class Contract extends Model
{

    use SoftDeletes;

    protected $fillable = [
    'c_number',
    'c_start',
    'c_end',
    'c_rent',
    'c_deposit',
    'c_status',
    'rentals_rt_id',
];

    protected $primaryKey = 'c_id';

    public function rental()
    {
        return $this->belongsTo(Rental::class, 'rentals_rt_id', 'rt_id');
    }
}
