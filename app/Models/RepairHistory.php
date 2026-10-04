<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

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
}
