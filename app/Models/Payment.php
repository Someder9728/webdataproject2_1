<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Invoice;

class Payment extends Model
{
    protected $fillable = [
    'p_date',
    'p_amount',
    'p_type',
    'p_status',
    'p_proof',
    'p_reject_reason',
    'invoices_i_id',
];

    public function invoice() {
        return $this->belongsTo(Invoice::class, 'invoices_i_id', 'i_id');
    }
}
