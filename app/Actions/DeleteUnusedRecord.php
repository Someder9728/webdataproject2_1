<?php

namespace App\Actions;

use App\Models\AuditEvent;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DeleteUnusedRecord
{
    public function handle(User $actor, Tenant|Room $record): void
    {
        DB::transaction(function () use ($actor, $record) {
            $actor = User::find($actor->getKey());

            abort_unless(
                $actor && ! $actor->must_change_password,
                403
            );

            Gate::forUser($actor)->authorize('delete', $record);

            $isTenant = $record instanceof Tenant;

            $current = $isTenant
                ? Tenant::findOrFail($record->getKey())
                : Room::findOrFail($record->getKey());

            $references = $isTenant
                ? [
                    ['users', 'tenants_t_id'],
                    ['rentals', 'tenants_t_id'],
                    ['repairs', 'tenants_t_id'],
                ]
                : [
                    ['rentals', 'rooms_r_id'],
                    ['meters', 'rooms_r_id'],
                    ['repairs', 'rooms_r_id'],
                ];

            // Query Builder รวมแถวที่ Soft Delete แล้วด้วย
            foreach ($references as [$table, $foreignKey]) {
                abort_if(
                    DB::table($table)
                        ->where($foreignKey, $current->getKey())
                        ->exists(),
                    409,
                    'ไม่สามารถลบรายการที่มีข้อมูลอ้างอิง'
                );
            }

            if (! $isTenant) {
                abort_unless($current->r_status === 'VACANT', 409);
            }

            $current->delete();

            AuditEvent::create([
                'actor_user_id' => $actor->getKey(),
                'entity_type' => $isTenant ? 'tenants' : 'rooms',
                'entity_id' => $current->getKey(),
                'action' => $isTenant ? 'tenant_deleted' : 'room_deleted',
                'old_values' => ['deleted_at' => null],
                'new_values' => [
                    'deleted_at' => $current->deleted_at->toISOString(),
                ],
            ]);
        }, 3);
    }
}