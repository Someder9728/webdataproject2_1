<?php

namespace App\Actions\Repairs;

use App\Models\AuditEvent;
use App\Models\Repair;
use App\Models\User;
use App\Support\SqliteTransaction;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UpdateRepairStatus
{
    public function __construct(private SqliteTransaction $transactions) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(User $actor, Repair $repair, array $input): Repair
    {
        return $this->transactions->run(function () use ($actor, $repair, $input) {
            $actor = User::whereKey($actor->getKey())->firstOrFail();
            $repair = Repair::whereKey($repair->getKey())->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('update', $repair);
            $data = Validator::make($input, [
                'expected_status' => ['required', Rule::in(['REPORTED', 'IN_PROGRESS', 'COMPLETED'])],
                'rp_status' => ['required', Rule::in(['IN_PROGRESS', 'COMPLETED'])],
            ])->validate();
            abort_unless($data['expected_status'] === $repair->rp_status, 409);
            $next = ['REPORTED' => 'IN_PROGRESS', 'IN_PROGRESS' => 'COMPLETED'];
            if (($next[$repair->rp_status] ?? null) !== $data['rp_status']) {
                throw ValidationException::withMessages(['rp_status' => 'ต้องเปลี่ยนจากแจ้งซ่อม เป็นกำลังซ่อม แล้วจึงเสร็จสิ้นตามลำดับ']);
            }
            $old = $repair->rp_status;
            $repair->update(['rp_status' => $data['rp_status']]);
            $repair->histories()->create([
                'rph_name' => $repair->rp_name, 'rph_description' => $repair->rp_description,
                'rph_type' => $repair->rp_type, 'rph_status' => $repair->rp_status,
                'changed_by_user_id' => $actor->getKey(),
            ]);
            AuditEvent::create([
                'actor_user_id' => $actor->getKey(), 'entity_type' => 'repairs',
                'entity_id' => $repair->getKey(), 'action' => 'repair_status_changed',
                'old_values' => ['rp_status' => $old],
                'new_values' => ['rp_status' => $repair->rp_status],
            ]);

            return $repair;
        });
    }
}
