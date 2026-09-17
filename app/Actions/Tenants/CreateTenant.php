<?php

namespace App\Actions\Tenants;

use App\Models\AuditEvent;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class CreateTenant
{
    public function handle(User $actor, array $input): Tenant
    {
        return DB::transaction(function () use ($actor, $input) {
            // อ่านสิทธิ์ล่าสุดภายใน transaction
            $currentActor = User::find($actor->getKey());

            abort_unless(
                $currentActor &&
                ! $currentActor->must_change_password,
                403
            );

            Gate::forUser($currentActor)
                ->authorize('create', Tenant::class);

            // รับเฉพาะข้อมูลผู้เช่า ไม่รับ ID หรือข้อมูลบัญชี
            $data = [];

            foreach ([
                't_Fname',
                't_Lname',
                't_tel',
                't_mail',
                't_address',
            ] as $field) {
                $value = $input[$field] ?? null;

                if (is_string($value)) {
                    $value = trim($value);
                    $value = $value === '' ? null : $value;
                }

                $data[$field] = $value;
            }

            $validated = Validator::make($data, [
                't_Fname' => ['required', 'string', 'max:255'],
                't_Lname' => ['required', 'string', 'max:255'],
                't_tel' => [
                    'required',
                    'string',
                    'regex:/\A[0-9]{10}\z/',
                ],
                't_mail' => ['nullable', 'string', 'email', 'max:255'],
                't_address' => ['nullable', 'string', 'max:2000'],
            ])->validate();

            $tenant = Tenant::create($validated);

            AuditEvent::create([
                'actor_user_id' => $currentActor->getKey(),
                'entity_type' => 'tenants',
                'entity_id' => $tenant->getKey(),
                'action' => 'tenant_created',
                'old_values' => null,
                'new_values' => $tenant->only([
                    't_Fname',
                    't_Lname',
                    't_tel',
                    't_mail',
                    't_address',
                ]),
            ]);

            return $tenant;
        }, 3);
    }
}