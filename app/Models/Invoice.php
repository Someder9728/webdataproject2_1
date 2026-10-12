<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $i_total
 * @property CarbonInterface $i_date
 * @property CarbonInterface $i_due
 * @property CarbonInterface|null $period_start
 * @property CarbonInterface|null $period_end
 */
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

    /** @return BelongsTo<Rental, $this> */
    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class, 'rentals_rt_id', 'rt_id');
    }

    /** @return HasOne<Payment, $this> */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class, 'invoices_i_id', 'i_id');
    }

    /** @return BelongsTo<Meter, $this> */
    public function startMeter(): BelongsTo
    {
        return $this->belongsTo(Meter::class, 'start_meter_id', 'm_id');
    }

    /** @return BelongsTo<Meter, $this> */
    public function endMeter(): BelongsTo
    {
        return $this->belongsTo(Meter::class, 'end_meter_id', 'm_id');
    }

    /**
     * @param  Builder<Invoice>  $query
     * @return Builder<Invoice>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->u_role !== 'tenant') {
            return $query;
        }

        if ($user->tenants_t_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('rental', fn ($rental) => $rental->where('tenants_t_id', $user->tenants_t_id));
    }

    /**
     * @param  Builder<Invoice>  $query
     * @return Builder<Invoice>
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        $numberId = preg_match('/^INV-([0-9]{4})-([0-9]+)$/i', $term, $numberParts) ? (int) $numberParts[2] : null;

        if ($term === '') {
            return $query;
        }

        return $query->where(function ($filter) use ($term, $numberId) {
            $filter->where('i_id', $numberId ?? (ctype_digit($term) ? (int) $term : -1))
                ->orWhereHas('rental.tenant', function ($tenant) use ($term) {
                    $tenant->whereRaw('instr(t_Fname, ?) > 0', [$term])
                        ->orWhereRaw('instr(t_Lname, ?) > 0', [$term])
                        ->orWhereRaw("instr(t_Fname || ' ' || t_Lname, ?) > 0", [$term]);
                })->orWhereHas('rental.room', fn ($room) => $room->whereRaw('instr(r_name, ?) > 0', [$term]));
        });
    }

    /**
     * @param  Builder<Invoice>  $query
     * @return Builder<Invoice>
     */
    public function scopeInMonth(Builder $query, ?string $month): Builder
    {
        if (empty($month)) {
            return $query;
        }

        $start = CarbonImmutable::createFromFormat('!Y-m', $month, 'Asia/Bangkok');

        return $query->whereDate('period_start', '>=', $start->toDateString())
            ->whereDate('period_start', '<', $start->addMonth()->toDateString());
    }

    public function number(): string
    {
        $year = ($this->i_date ?? $this->period_start)?->format('Y') ?? '----';

        return 'INV-'.$year.'-'.str_pad((string) $this->getKey(), 4, '0', STR_PAD_LEFT);
    }

    public function statusKey(): string
    {
        return match ($this->payment?->p_status) {
            'PAID' => 'paid',
            'PENDING' => 'pending',
            'REJECTED' => 'rejected',
            'UNPAID' => $this->isPastDue() ? 'overdue' : 'unpaid',
            default => 'none',
        };
    }

    public function isPastDue(): bool
    {
        return $this->i_due->toDateString() < now('Asia/Bangkok')->toDateString();
    }

    public function daysLate(): int
    {
        $today = CarbonImmutable::parse(now('Asia/Bangkok')->toDateString());

        return (int) CarbonImmutable::parse($this->i_due->toDateString())->diffInDays($today);
    }

    public function canRecordPayment(): bool
    {
        return in_array($this->payment?->p_status, ['UNPAID', 'REJECTED'], true);
    }

    public function isEditable(): bool
    {
        return $this->payment?->p_status === 'UNPAID' && ! $this->payment->events()->exists();
    }
}
