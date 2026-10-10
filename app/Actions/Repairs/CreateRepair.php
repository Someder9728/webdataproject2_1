<?php

namespace App\Actions\Repairs;

use App\Models\AuditEvent;
use App\Models\Rental;
use App\Models\Repair;
use App\Models\User;
use App\Support\SqliteTransaction;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateRepair
{
    public function __construct(private SqliteTransaction $transactions) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(User $actor, array $input): Repair
    {
        return $this->transactions->run(function () use ($actor, $input) {
            $actor = User::whereKey($actor->getKey())->firstOrFail();
            Gate::forUser($actor)->authorize('create', Repair::class);
            $data = Validator::make($input, [
                'rp_name' => ['required', 'string', 'max:255'],
                'rp_description' => ['nullable', 'string', 'max:10000'],
                'rp_type' => ['required', Rule::in(['ROOM', 'COMMON'])],
                'rooms_r_id' => ['required_if:rp_type,ROOM', 'prohibited_if:rp_type,COMMON', 'nullable', 'integer', Rule::exists('rooms', 'r_id')->whereNull('deleted_at')],
                'tenants_t_id' => ['nullable', 'integer', Rule::exists('tenants', 't_id')->whereNull('deleted_at')],
            ])->validate();

            if ($actor->u_role === 'tenant') {
                abort_unless($actor->tenant()->exists(), 403);
                $data['tenants_t_id'] = $actor->tenants_t_id;
                $rentals = Rental::where('tenants_t_id', $actor->tenants_t_id)
                    ->where('rt_status', 'ACTIVE')->whereHas('room');
                if ($data['rp_type'] === 'ROOM') {
                    $rentals->where('rooms_r_id', $data['rooms_r_id']);
                }
                abort_unless($rentals->exists(), 403);
            }

            $repair = Repair::create([
                ...$data,
                'rooms_r_id' => $data['rp_type'] === 'COMMON' ? null : $data['rooms_r_id'],
                'rp_status' => 'REPORTED',
                'reported_by_user_id' => $actor->getKey(),
            ]);
            $repair->histories()->create([
                'rph_name' => $repair->rp_name,
                'rph_description' => $repair->rp_description,
                'rph_type' => $repair->rp_type,
                'rph_status' => 'REPORTED',
                'changed_by_user_id' => $actor->getKey(),
            ]);
            AuditEvent::create([
                'actor_user_id' => $actor->getKey(),
                'entity_type' => 'repairs', 'entity_id' => $repair->getKey(),
                'action' => 'repair_created', 'new_values' => $repair->getAttributes(),
            ]);

            return $repair;
        });
    }
}
