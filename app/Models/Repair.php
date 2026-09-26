<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Tenant;
use App\Models\Room;
use App\Models\User;
use App\Models\RepairHistory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;


class Repair extends Model
{

    use SoftDeletes;

    /** ประเภทงานซ่อม: ภายในห้อง หรือ พื้นที่ส่วนกลาง */
    public const TYPES = ['ROOM', 'COMMON'];

    /** ลำดับสถานะงานซ่อม: แจ้งแล้ว → กำลังซ่อม → เสร็จแล้ว (ห้ามข้ามขั้น) */
    public const STATUSES = ['REPORTED', 'IN_PROGRESS', 'COMPLETED'];

    protected $fillable = [
    'rp_name',
    'rp_description',
    'rp_status',
    'rp_type',
    'tenants_t_id',
    'rooms_r_id',
    'reported_by_user_id',
];

    protected $primaryKey = 'rp_id';

    /**
     * บังคับที่ระดับ Model เพื่อให้ error message อ่านง่ายกว่ารอ
     * SQLite trigger (repairs_type_room_insert/update) ที่มีอยู่แล้ว
     * ที่ฝั่ง DB มา abort เป็น query exception ดิบ ๆ
     */
    protected static function booted(): void
    {
        static::saving(function (Repair $repair) {
            if (! in_array($repair->rp_type, self::TYPES, true)) {
                throw ValidationException::withMessages([
                    'rp_type' => 'ประเภทงานซ่อมต้องเป็นหนึ่งใน: '
                        . implode(', ', self::TYPES) . ' เท่านั้น',
                ]);
            }

            if (! in_array($repair->rp_status, self::STATUSES, true)) {
                throw ValidationException::withMessages([
                    'rp_status' => 'สถานะงานซ่อมต้องเป็นหนึ่งใน: '
                        . implode(', ', self::STATUSES) . ' เท่านั้น',
                ]);
            }

            if ($repair->rp_type === 'ROOM' && ! $repair->rooms_r_id) {
                throw ValidationException::withMessages([
                    'rooms_r_id' => 'งานซ่อมประเภท ROOM ต้องระบุห้อง',
                ]);
            }

            if ($repair->rp_type === 'COMMON' && $repair->rooms_r_id) {
                throw ValidationException::withMessages([
                    'rooms_r_id' => 'งานซ่อมประเภท COMMON ต้องไม่ระบุห้อง',
                ]);
            }
            if ($repair->rp_type === 'ROOM' && ! $repair->tenants_t_id) {
                throw ValidationException::withMessages([
                    'tenants_t_id' => 'งานซ่อมประเภท ROOM ต้องระบุผู้เช่า',
                ]);
            }

            if ($repair->rp_type === 'COMMON' && $repair->tenants_t_id) {
                throw ValidationException::withMessages([
                    'tenants_t_id' => 'งานซ่อมประเภท COMMON ต้องไม่มีผู้เช่า',
                ]);
            }
        });
    }

    public function tenant() {
        return $this->belongsTo(Tenant::class, 'tenants_t_id', 't_id');
    }

    public function room() {
        return $this->belongsTo(Room::class, 'rooms_r_id', 'r_id');
    }

    public function histories() {
        return $this->hasMany(RepairHistory::class, 'repairs_rp_id', 'rp_id');
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by_user_id', 'u_id');
    }
}
