<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $p_amount
 * @property CarbonInterface|null $p_date
 */
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

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoices_i_id', 'i_id');
    }

    /** @return HasMany<PaymentEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(
            PaymentEvent::class,
            'payments_p_id',
            'p_id'
        )
            ->orderBy('created_at')
            ->orderBy('pe_id');
    }

    protected function casts(): array
    {
        return [
            'p_amount' => 'decimal:2',
            'p_date' => 'date',
        ];
    }
}
