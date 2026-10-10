<?php

namespace App\Actions\Rentals;

use App\Models\AuditEvent;
use App\Models\Contract;
use App\Models\Rental;
use App\Models\User;
use App\Support\SqliteTransaction;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UpdateContract
{
    public function __construct(private SqliteTransaction $transactions) {}

    /** @param array<string, mixed> $input */
    public function handle(User $actor, Rental $rental, array $input): Contract
    {
        return $this->transactions->run(function () use ($actor, $rental, $input) {
            $actor = User::whereKey($actor->getKey())->first();
            abort_unless($actor && $actor->is_active && ! $actor->must_change_password && $actor->u_role === 'admin', 403);
            $rental = Rental::whereKey($rental->getKey())->firstOrFail();
            $contract = $rental->contract()->firstOrFail();
            abort_unless($rental->rt_status === 'ACTIVE' && $rental->rt_moveout === null && in_array($contract->c_status, ['ACTIVE', 'EXPIRED'], true), 409);

            $input['reason'] = is_string($input['reason'] ?? null) ? trim($input['reason']) : ($input['reason'] ?? null);
            $validated = Validator::make($input, [
                'c_end' => ['present', 'nullable', 'date_format:Y-m-d', 'after:'.$contract->c_start->toDateString()],
                'reason' => ['required', 'string', 'max:500'],
                'c_rent' => ['prohibited'],
                'c_deposit' => ['prohibited'],
                'c_start' => ['prohibited'],
                'c_status' => ['prohibited'],
                'c_number' => ['prohibited'],
                'rentals_rt_id' => ['prohibited'],
            ])->validate();
            $before = ['c_end' => $contract->c_end?->toDateString(), 'c_status' => $contract->c_status];
            if ($before['c_end'] === $validated['c_end']) {
                throw ValidationException::withMessages(['c_end' => 'ไม่มีข้อมูลเปลี่ยนแปลง']);
            }

            $contract->c_end = $validated['c_end'];
            // Contract expiry is a date; only MoveOutRental ends occupancy.
            $contract->c_status = $validated['c_end'] !== null && $validated['c_end'] < now('Asia/Bangkok')->toDateString() ? 'EXPIRED' : 'ACTIVE';
            $contract->save();
            AuditEvent::create([
                'actor_user_id' => $actor->getKey(), 'entity_type' => 'contracts',
                'entity_id' => $contract->getKey(), 'action' => 'contract_updated',
                'old_values' => $before,
                'new_values' => ['c_end' => $contract->c_end?->toDateString(), 'c_status' => $contract->c_status],
                'reason' => $validated['reason'],
            ]);

            return $contract;
        });
    }
}
