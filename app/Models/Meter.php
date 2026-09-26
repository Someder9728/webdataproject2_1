<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Room;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class Meter extends Model
{

    use SoftDeletes;

    protected $fillable = [
    'm_date',
    'm_water',
    'm_elec',
    'm_type',
    'rooms_r_id',
];
    public const TYPES = ['move_in', 'monthly', 'move_out'];

    protected $primaryKey = 'm_id';

    public function room() {
        return $this->belongsTo(Room::class, 'rooms_r_id', 'r_id');
    }
    /**
     * บังคับว่า m_type ต้องเป็นหนึ่งใน self::TYPES เท่านั้น
     * ทำที่ระดับ Model เพื่อกันทุกช่องทางที่สร้าง/แก้ Meter
     * ไม่ใช่แค่ Action เดียว
     */
    protected static function booted(): void
    {
        static::saving(function (Meter $meter) {
            if (! in_array($meter->m_type, self::TYPES, true)) {
                throw ValidationException::withMessages([
                    'm_type' => 'ประเภทมิเตอร์ต้องเป็นหนึ่งใน: '
                        . implode(', ', self::TYPES) . ' เท่านั้น',
                ]);
            }
        });
    } 
    
    
    /*หา Meter record ก่อนหน้าของห้องเดียวกัน
    */ 
    public function previousReading(): ?self
    {
        return static::query()
            ->where('rooms_r_id', $this->rooms_r_id)
            ->where('m_date', '<', $this->m_date)
            ->orderByDesc('m_date')
            ->orderByDesc('m_id')
            ->first();
    }

    /**
     * หา Meter record ถัดไปของห้องเดียวกัน (ตรงข้ามกับ previousReading)
     * ใช้หาปลายช่วงของรอบบิลเมื่อรู้แค่ต้นช่วง
     */
    public function nextReading(): ?self
    {
        return static::query()
            ->where('rooms_r_id', $this->rooms_r_id)
            ->where('m_date', '>', $this->m_date)
            ->orderBy('m_date')
            ->orderBy('m_id')
            ->first();
    }

    public function usage(): ?array
    {
        $previous = $this->previousReading();
 
        if (! $previous) {
            return null;
        }
 
        return [
            'water' => (float) $this->m_water - (float) $previous->m_water,
            'elec' => (float) $this->m_elec - (float) $previous->m_elec,
        ];
    }
}
