<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Rental;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\SoftDeletes;


class Contract extends Model
{

    use SoftDeletes;

    protected $fillable = [
    'c_number',
    'c_start',
    'c_end',
    'c_rent',
    'c_deposit',
    'c_status',
    'rentals_rt_id',
];

    protected $primaryKey = 'c_id';

    protected $casts = [
        'c_start'   => 'date:Y-m-d',
        'c_end'     => 'date:Y-m-d',
        'c_rent'    => 'decimal:2',
        'c_deposit' => 'decimal:2',
    ];

    // TODO(ยืนยันกับเกลือ): ค่าที่แท้จริงของ c_status
    public const STATUS_ACTIVE  = 'ACTIVE';
    public const STATUS_EXPIRED = 'EXPIRED';
    public const STATUS_ENDED   = 'ENDED';

    public function invoices()
    {
        return Invoice::query()->where('rentals_rt_id', $this->rentals_rt_id);
    }

    public function isPriceLocked(): bool
    {
        return $this->invoices()->exists();
    }

    public function priceLockedReason(): ?string
    {
        if (! $this->isPriceLocked()) return null;
        $firstInvoice = $this->invoices()->oldest('created_at')->first();
        return $firstInvoice
            ? "มี Invoice ใบแรกแล้ว (i_id={$firstInvoice->i_id}) เมื่อ {$firstInvoice->created_at->format('Y-m-d')}"
            : 'มี Invoice ออกไปแล้ว';
    }
}