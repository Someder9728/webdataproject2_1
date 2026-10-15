<?php

namespace App\Actions\Rentals;

use App\Models\AuditEvent;
use App\Models\Meter;
use App\Models\Rental;
use App\Models\Room;
use App\Models\User;
use App\Services\RentalAvailabilityService;
use App\Support\SqliteTransaction;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ActivateRental
{
    public function __construct(private SqliteTransaction $transactions, private RentalAvailabilityService $availability) {}

    public function handle(User $actor, Rental $rental): Rental
    {
        return $this->transactions->run(function () use ($actor, $rental) {
            $actor = User::whereKey($actor->getKey())->firstOrFail();
            Gate::forUser($actor)->authorize('moveOut', $rental);
            $rental = Rental::whereKey($rental->getKey())->firstOrFail();
            $today = now('Asia/Bangkok')->toDateString();
            abort_unless($rental->rt_status === 'SCHEDULED' && $rental->scheduled_movein_at !== null, 409);
            abort_if($today < $rental->scheduled_movein_at->toDateString(), 409, 'ยังไม่ถึงกำหนดเข้าพัก');
            $room = Room::whereKey($rental->rooms_r_id)->firstOrFail();
            abort_unless($room->r_status === 'VACANT' && ! $room->rentals()->where('rt_status', 'ACTIVE')->exists(), 409, 'ห้องยังไม่พร้อมเข้าอยู่');
            $contract = $rental->contract()->firstOrFail();
            abort_unless($contract->c_status === 'SCHEDULED', 409);
            if ($contract->c_end !== null && $contract->c_end->toDateString() < $today) {
                throw ValidationException::withMessages(['c_end' => 'วันสิ้นสุดสัญญาผ่านไปแล้ว กรุณายกเลิกและสร้างรายการใหม่']);
            }
            $this->availability->assertAvailable((int) $room->getKey(), (int) $rental->tenants_t_id, $today, null, (int) $rental->getKey());
            if (! Meter::where('rooms_r_id', $room->getKey())->whereDate('m_date', $today)->exists()) {
                throw ValidationException::withMessages(['rt_movein' => 'ต้องบันทึกมิเตอร์ตรงวันเข้าพักจริงก่อน']);
            }
            $rental->update(['rt_movein' => $today, 'rt_status' => 'ACTIVE']);
            $contract->update(['c_start' => $today, 'c_status' => 'ACTIVE']);
            $room->update(['r_status' => 'OCCUPIED']);
            AuditEvent::create(['actor_user_id' => $actor->getKey(), 'entity_type' => 'rentals', 'entity_id' => $rental->getKey(), 'action' => 'rental_activated',
                'old_values' => ['rt_status' => 'SCHEDULED', 'rt_movein' => null, 'c_start' => $rental->scheduled_movein_at->toDateString()],
                'new_values' => ['rt_status' => 'ACTIVE', 'rt_movein' => $today, 'c_start' => $today, 'r_status' => 'OCCUPIED']]);

            return $rental->load(['room', 'tenant', 'contract']);
        });
    }
}
