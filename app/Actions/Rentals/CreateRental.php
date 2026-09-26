<?php

namespace App\Actions\Rentals;

use App\Models\AuditEvent;
use App\Models\Meter;
use App\Models\Rental;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CreateRental
{
    public function handle(User $actor, array $input): Rental
    {
        return DB::transaction(function () use ($actor, $input) {
            $actor = User::find($actor->getKey());

            abort_unless(
                $actor && ! $actor->must_change_password,
                403
            );

            Gate::forUser($actor)->authorize('create', Rental::class);

            $today = now('Asia/Bangkok')->toDateString();

            $data = [];

            foreach ([
                'tenants_t_id',
                'rooms_r_id',
                'c_end',
                'c_rent',
                'c_deposit',
            ] as $field) {
                if (array_key_exists($field, $input)) {
                    $value = $input[$field];

                    $data[$field] = is_string($value)
                        ? trim($value)
                        : $value;
                }
            }

            if (($data['c_end'] ?? null) === '') {
                $data['c_end'] = null;
            }

            $moneyRules = [
                'required',
                'numeric',
                'regex:/\A[0-9]{1,8}(?:\.[0-9]{1,2})?\z/',
            ];

            $validated = Validator::make($data, [
                'tenants_t_id' => ['required', 'integer', 'min:1'],
                'rooms_r_id' => ['required', 'integer', 'min:1'],
                'c_end' => [
                    'nullable',
                    'date_format:Y-m-d',
                    'after_or_equal:'.$today,
                ],
                'c_rent' => $moneyRules,
                'c_deposit' => $moneyRules,
            ])->validate();

            $tenant = Tenant::find($validated['tenants_t_id']);
            $room = Room::find($validated['rooms_r_id']);

            if (! $tenant) {
                throw ValidationException::withMessages([
                    'tenants_t_id' => 'ไม่พบผู้เช่าที่ใช้งานอยู่',
                ]);
            }

            if (! $room) {
                throw ValidationException::withMessages([
                    'rooms_r_id' => 'ไม่พบห้องที่ใช้งานอยู่',
                ]);
            }

            abort_unless($room->r_status === 'VACANT', 409);

            $hasActiveRental = Rental::query()
                ->where('rt_status', 'ACTIVE')
                ->where(function ($query) use ($tenant, $room) {
                    $query->where('tenants_t_id', $tenant->getKey())
                        ->orWhere('rooms_r_id', $room->getKey());
                })
                ->exists();

            abort_if($hasActiveRental, 409);

            $hasOpeningMeter = Meter::query()
                ->where('rooms_r_id', $room->getKey())
                ->where('m_date', $today)
                ->exists();

            if (! $hasOpeningMeter) {
                throw ValidationException::withMessages([
                    'rooms_r_id' =>
                        'ต้องบันทึกมิเตอร์ของห้อง ณ วันเข้าพักก่อน',
                ]);
            }

            $rental = Rental::create([
                'tenants_t_id' => $tenant->getKey(),
                'rooms_r_id' => $room->getKey(),
                'rt_movein' => $today,
                'rt_moveout' => null,
                'rt_status' => 'ACTIVE',
            ]);

            $contract = $rental->contract()->create([
                'c_number' => 'CNT-'.str_pad(
                    (string) $rental->getKey(),
                    6,
                    '0',
                    STR_PAD_LEFT
                ),
                'c_start' => $today,
                'c_end' => $validated['c_end'] ?? null,
                'c_rent' => $validated['c_rent'],
                'c_deposit' => $validated['c_deposit'],
                'c_status' => 'ACTIVE',
            ]);

            $room->r_status = 'OCCUPIED';
            $room->save();

            AuditEvent::create([
                'actor_user_id' => $actor->getKey(),
                'entity_type' => 'rentals',
                'entity_id' => $rental->getKey(),
                'action' => 'rental_created',
                'old_values' => null,
                'new_values' => [
                    'tenants_t_id' => $tenant->getKey(),
                    'rooms_r_id' => $room->getKey(),
                    'rt_movein' => $today,
                    'rt_status' => 'ACTIVE',
                    'contract' => $contract->only([
                        'c_id',
                        'c_number',
                        'c_start',
                        'c_end',
                        'c_rent',
                        'c_deposit',
                        'c_status',
                    ]),
                ],
            ]);

            AuditEvent::create([
                'actor_user_id' => $actor->getKey(),
                'entity_type' => 'rooms',
                'entity_id' => $room->getKey(),
                'action' => 'room_updated',
                'old_values' => ['r_status' => 'VACANT'],
                'new_values' => ['r_status' => 'OCCUPIED'],
                'reason' => 'รับเข้าพัก Rental '.$rental->getKey(),
            ]);

            return $rental->load(['tenant', 'room', 'contract']);
        }, 3);
    }
}