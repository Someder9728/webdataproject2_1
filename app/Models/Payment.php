<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class Payment extends Model
{

    use SoftDeletes;

    /** ลำดับสถานะการชำระเงิน: รอดำเนินการ → จ่ายแล้ว / ถูกปฏิเสธ */
    public const STATUSES = ['PENDING', 'PAID', 'REJECTED'];

    protected $fillable = [
    'p_date',
    'p_amount',
    'p_type',
    'p_status',
    'p_proof',
    'p_reject_reason',
    'invoices_i_id',
];

    protected $primaryKey = 'p_id';

    protected static function booted(): void
    {
        static::saving(function (Payment $payment) {
            if (! in_array($payment->p_status, self::STATUSES, true)) {
                throw ValidationException::withMessages([
                    'p_status' => 'สถานะการชำระเงินต้องเป็นหนึ่งใน: '
                        . implode(', ', self::STATUSES) . ' เท่านั้น',
                ]);
            }
        });

        // เมื่อ Payment ออกจาก PENDING แล้ว (PAID/REJECTED) ถือว่าจบ
        // กระบวนการ ห้ามแก้ไขอีก การเปลี่ยนจาก PENDING ไป PAID/REJECTED
        // ครั้งแรกยังทำได้ปกติ (เช็คจากค่าดั้งเดิมก่อนแก้ ไม่ใช่ค่าที่กำลังจะเซฟ)
        static::updating(function (Payment $payment) {
            $originalStatus = $payment->getOriginal('p_status');

            if ($originalStatus !== null && $originalStatus !== 'PENDING') {
                throw ValidationException::withMessages([
                    'p_status' => 'ไม่สามารถแก้ไขรายการชำระเงินที่ดำเนินการแล้วได้ (PAID/REJECTED)',
                ]);
            }
        });
    }

    public function invoice() {
        return $this->belongsTo(Invoice::class, 'invoices_i_id', 'i_id');
    }


    public function events(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(
            PaymentEvent::class,
            'payments_p_id',
            'p_id'
        )
            ->orderBy('created_at')
            ->orderBy('pe_id');
    }
}
