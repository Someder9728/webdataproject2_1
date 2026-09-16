<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $primaryKey = 'i_id';

    protected $fillable = [
        'i_date',
        'i_rent',
        'i_water',
        'i_elec',
        'i_total',
        'i_due',
        'rentals_rt_id',
        'period_start',
        'period_end',
        'start_meter_id',
        'end_meter_id',
        'water_usage',
        'elec_usage',
        'water_rate',
        'elec_rate',
        'rent_rate',
    ];

    protected function casts(): array
    {
        return [
            'i_date' => 'date',
            'i_due' => 'date',
            'period_start' => 'date',
            'period_end' => 'date',
            'i_rent' => 'decimal:2',
            'i_water' => 'decimal:2',
            'i_elec' => 'decimal:2',
            'i_total' => 'decimal:2',
            'water_usage' => 'decimal:2',
            'elec_usage' => 'decimal:2',
            'water_rate' => 'decimal:2',
            'elec_rate' => 'decimal:2',
            'rent_rate' => 'decimal:2',
        ];
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class, 'rentals_rt_id', 'rt_id');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class, 'invoices_i_id', 'i_id');
    }

    public function startMeter(): BelongsTo
    {
        return $this->belongsTo(Meter::class, 'start_meter_id', 'm_id');
    }

    public function endMeter(): BelongsTo
    {
        return $this->belongsTo(Meter::class, 'end_meter_id', 'm_id');
    }
}