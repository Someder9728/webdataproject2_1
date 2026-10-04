<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/** @property Carbon $m_date */
class Meter extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'm_date',
        'm_water',
        'm_elec',
        'rooms_r_id',
    ];

    protected $primaryKey = 'm_id';

    protected function casts(): array
    {
        return [
            'm_date' => 'date:Y-m-d',
            'm_water' => 'decimal:2',
            'm_elec' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Room, $this> */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'rooms_r_id', 'r_id');
    }
}
