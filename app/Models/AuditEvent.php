<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id', 'u_id');
    }
}