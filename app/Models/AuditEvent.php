<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<string, mixed>|null $old_values
 * @property array<string, mixed>|null $new_values
 */
class AuditEvent extends Model
{
    protected $primaryKey = 'ae_id';

    public const UPDATED_AT = null;

    protected $fillable = [
        'actor_user_id',
        'entity_type',
        'entity_id',
        'action',
        'old_values',
        'new_values',
        'reason',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id', 'u_id');
    }
}
