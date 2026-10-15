<?php

namespace App\Actions\Rentals;

use App\Models\AuditEvent;
use App\Models\Rental;
use App\Models\User;
use App\Services\RentalAvailabilityService;
use App\Support\SqliteTransaction;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class ScheduleMoveOut
{
    public function __construct(private SqliteTransaction $transactions, private RentalAvailabilityService $availability) {}

    /** @param array<string,mixed> $input */
    public function handle(User $actor, Rental $rental, array $input): Rental
    {
        return $this->transactions->run(function () use ($actor, $rental, $input) {
            $actor = User::whereKey($actor->getKey())->firstOrFail();
            Gate::forUser($actor)->authorize('moveOut', $rental);
            $rental = Rental::whereKey($rental->getKey())->firstOrFail();
            abort_unless($rental->rt_status === 'ACTIVE' && $rental->rt_movein !== null, 409);
            $data = Validator::make($input, [
                'scheduled_moveout_at' => ['present', 'nullable', 'date_format:Y-m-d', 'after:'.now('Asia/Bangkok')->toDateString(), 'after:'.$rental->rt_movein->toDateString()],
                'reason' => ['required', 'string', 'max:2000', 'regex:/\S/u'],
            ])->validate();
            $this->availability->assertAvailable((int) $rental->rooms_r_id, (int) $rental->tenants_t_id, $rental->rt_movein->toDateString(), $data['scheduled_moveout_at'], (int) $rental->getKey());
            $old = $rental->scheduled_moveout_at?->toDateString();
            $rental->update(['scheduled_moveout_at' => $data['scheduled_moveout_at']]);
            AuditEvent::create(['actor_user_id' => $actor->getKey(), 'entity_type' => 'rentals', 'entity_id' => $rental->getKey(), 'action' => 'moveout_schedule_changed',
                'old_values' => ['scheduled_moveout_at' => $old], 'new_values' => ['scheduled_moveout_at' => $data['scheduled_moveout_at']], 'reason' => trim($data['reason'])]);

            return $rental;
        });
    }
}
