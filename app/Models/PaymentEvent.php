<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentEvent extends Model
{
    protected $primaryKey = 'pe_id';

    public const UPDATED_AT = null;

    protected $fillable = [
        'payments_p_id',
        'actor_user_id',
        'event_type',
        'from_status',
        'to_status',
        'amount',
        'payment_date',
        'method',
        'proof_path',
        'reason',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payments_p_id', 'p_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id', 'u_id');
    }

    public function events(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PaymentEvent::class, 'payments_p_id', 'p_id')
            ->orderBy('created_at')
            ->orderBy('pe_id');
    }
}