<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Rental;
use App\Models\Payment;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{

    use SoftDeletes;

    protected $fillable = [
    'i_date',
    'i_rent',
    'i_water',
    'i_elec',
    'i_total',
    'i_due',
    'rentals_rt_id',
];

    protected $primaryKey = 'i_id';

    public function payment() {
        return $this->hasOne(Payment::class, 'invoices_i_id', 'i_id');
    }

    public function rental() {
        return $this->belongsTo(Rental::class,'rentals_rt_id','rt_id');
    }

}
