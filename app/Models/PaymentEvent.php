<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Read projection over audit_events via the payment_events SQL view.
 *
 * @property int $pe_id
 * @property int $payments_p_id
 * @property int $actor_user_id
 * @property string $event_type
 * @property string|null $from_status
 * @property string $to_status
 * @property string $amount
 * @property string|null $method
 * @property string|null $proof_path
 * @property string|null $reason
 * @property string|null $note
 * @property int|null $audit_reviewed_event_id
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $payment_date
 */
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
        'audit_reviewed_event_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Payment, $this> */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payments_p_id', 'p_id');
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id', 'u_id');
    }

    /** The SQL view is read-only; each write creates exactly one central Audit row.
     * @param  Builder<static>  $query
     */
    protected function performInsert(Builder $query): bool
    {
        if ($this->fireModelEvent('creating') === false) {
            return false;
        }
        $this->updateTimestamps();
        $history = $this->only(['event_type', 'from_status', 'to_status', 'amount', 'method', 'proof_path', 'note']);
        $history['payment_date'] = $this->payment_date?->toDateString();
        $history['kind'] = 'payment';
        $new = ['p_status' => $this->to_status, 'p_amount' => $this->amount, 'p_date' => $history['payment_date'],
            'p_type' => $this->method, 'p_reject_reason' => $this->to_status === 'REJECTED' ? $this->reason : null,
            'note' => $this->note, 'history' => $history];
        if ($this->audit_reviewed_event_id !== null) {
            $new['reviewed_event_id'] = $this->audit_reviewed_event_id;
        }
        $audit = AuditEvent::create(['actor_user_id' => $this->actor_user_id, 'entity_type' => 'payments', 'entity_id' => $this->payments_p_id,
            'action' => match ($this->event_type) {
                'PROOF_SUBMITTED' => 'payment_proof_submitted', 'PAYMENT_APPROVED' => 'payment_approved',
                'PAYMENT_REJECTED' => 'payment_rejected', 'WALK_IN_RECORDED' => 'walk_in_payment_recorded', default => 'payment_history_recorded',
            },
            'old_values' => ['p_status' => $this->from_status], 'new_values' => $new,
            'reason' => $this->reason, 'created_at' => $this->created_at]);
        $this->setAttribute('pe_id', $audit->getKey());
        $this->exists = true;
        $this->wasRecentlyCreated = true;
        $this->fireModelEvent('created', false);

        return true;
    }
}
