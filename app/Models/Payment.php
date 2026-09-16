<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{

    use SoftDeletes;

    protected $fillable = [
    'p_date',
    'p_amount',
    'p_type',
    'p_status',
    'p_proof',
    'p_reject_reason',
    'invoices_i_id',
];

    protected $primaryKey = 'p_id';

    public function invoice() {
        return $this->belongsTo(Invoice::class, 'invoices_i_id', 'i_id');
    }


    public function events(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(
            PaymentEvent::class,
            'payments_p_id',
            'p_id'
        )
            ->orderBy('created_at')
            ->orderBy('pe_id');
    }
}
