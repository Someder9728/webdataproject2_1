<?php

namespace App\Actions\Tenants;

use App\Models\AuditEvent;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class UpdateTenant
{
    public function handle(
        User $actor,
        Tenant $tenant,
        array $input
    ): Tenant {
        return DB::transaction(function () use ($actor, $tenant, $input) {
            $currentActor = User::find($actor->getKey());

            abort_unless(
                $currentActor &&
                ! $currentActor->must_change_password,
                403
            );

            // ตรวจสิทธิ์ก่อนอ่านข้อมูลเป้าหมายจากฐานข้อมูล
            Gate::forUser($currentActor)->authorize('update', $tenant);

            $currentTenant = Tenant::findOrFail($tenant->getKey());

            $data = [];

            foreach ([
                't_Fname',
                't_Lname',
                't_tel',
                't_mail',
                't_address',
            ] as $field) {
                if (! array_key_exists($field, $input)) {
                    continue;
                }

                $value = $input[$field];

                if (is_string($value)) {
                    $value = trim($value);
                    $value = $value === '' ? null : $value;
                }

                $data[$field] = $value;
            }

            $validated = Validator::make($data, [
                't_Fname' => ['sometimes', 'required', 'string', 'max:255'],
                't_Lname' => ['sometimes', 'required', 'string', 'max:255'],
                't_tel' => [
                    'sometimes',
                    'required',
                    'string',
                    'regex:/\A[0-9]{10}\z/',
                ],
                't_mail' => ['sometimes', 'nullable', 'string', 'email', 'max:255'],
                't_address' => ['sometimes', 'nullable', 'string', 'max:2000'],
            ])->validate();

            $currentTenant->fill($validated);

            $changes = $currentTenant->getDirty();

            if ($changes === []) {
                return $currentTenant;
            }

            $oldValues = [];

            foreach (array_keys($changes) as $field) {
                $oldValues[$field] = $currentTenant->getOriginal($field);
            }

            $currentTenant->save();

            AuditEvent::create([
                'actor_user_id' => $currentActor->getKey(),
                'entity_type' => 'tenants',
                'entity_id' => $currentTenant->getKey(),
                'action' => 'tenant_updated',
                'old_values' => $oldValues,
                'new_values' => $currentTenant->only(array_keys($changes)),
            ]);

            return $currentTenant;
        }, 3);
    }
}