<?php

namespace App\Actions\Rentals;

use App\Models\AuditEvent;
use App\Models\Rental;
use App\Models\User;
use App\Support\SqliteTransaction;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class CancelScheduledRental
{
    public function __construct(private SqliteTransaction $transactions) {}

    /** @param array<string,mixed> $input */
    public function handle(User $actor, Rental $rental, array $input): Rental
    {
        return $this->transactions->run(function () use ($actor, $rental, $input) {
            $actor = User::whereKey($actor->getKey())->firstOrFail();
            Gate::forUser($actor)->authorize('moveOut', $rental);
            $rental = Rental::whereKey($rental->getKey())->firstOrFail();
            abort_unless($rental->rt_status === 'SCHEDULED', 409);
            $data = Validator::make($input, ['reason' => ['required', 'string', 'max:2000', 'regex:/\S/u']])->validate();
            abort_if($rental->invoices()->withTrashed()->exists(), 409);
            $contract = $rental->contract()->firstOrFail();
            $rental->update(['rt_status' => 'CANCELLED']);
            $contract->update(['c_status' => 'CANCELLED']);
            AuditEvent::create(['actor_user_id' => $actor->getKey(), 'entity_type' => 'rentals', 'entity_id' => $rental->getKey(), 'action' => 'scheduled_rental_cancelled',
                'old_values' => ['rt_status' => 'SCHEDULED'], 'new_values' => ['rt_status' => 'CANCELLED', 'c_status' => 'CANCELLED'], 'reason' => trim($data['reason'])]);

            return $rental;
        });
    }
}
