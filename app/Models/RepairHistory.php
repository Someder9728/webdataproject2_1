<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Read projection over audit_events via the repair_histories SQL view.
 *
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property int $rph_id
 * @property int $repairs_rp_id
 * @property int $changed_by_user_id
 * @property string|null $rph_name
 * @property string|null $rph_description
 * @property string $rph_status
 * @property string|null $rph_type
 * @property string|null $previous_status
 */
class RepairHistory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'rph_name',
        'rph_description',
        'rph_status',
        'rph_type',
        'repairs_rp_id',
        'changed_by_user_id',
        'previous_status',
    ];

    protected $primaryKey = 'rph_id';

    /** @return BelongsTo<Repair, $this> */
    public function repair(): BelongsTo
    {
        return $this->belongsTo(Repair::class, 'repairs_rp_id', 'rp_id');
    }

    /** @return BelongsTo<User, $this> */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id', 'u_id');
    }

    /** @param Builder<static> $query */
    protected function performInsert(Builder $query): bool
    {
        if ($this->fireModelEvent('creating') === false) {
            return false;
        }
        $this->updateTimestamps();
        $repair = $this->repair()->firstOrFail();
        $history = $this->only(['rph_name', 'rph_description', 'rph_status', 'rph_type']);
        $history['kind'] = 'repair';
        $history['updated_at'] = $this->updated_at?->format('Y-m-d H:i:s');
        $history['deleted_at'] = null;
        $audit = AuditEvent::create(['actor_user_id' => $this->changed_by_user_id, 'entity_type' => 'repairs', 'entity_id' => $this->repairs_rp_id,
            'action' => $this->rph_status === 'REPORTED' ? 'repair_created' : 'repair_status_changed',
            'old_values' => $this->previous_status === null ? null : ['rp_status' => $this->previous_status],
            'new_values' => [...$repair->getAttributes(), 'history' => $history], 'created_at' => $this->created_at]);
        $this->setAttribute('rph_id', $audit->getKey());
        $this->exists = true;
        $this->wasRecentlyCreated = true;
        $this->fireModelEvent('created', false);

        return true;
    }
}
