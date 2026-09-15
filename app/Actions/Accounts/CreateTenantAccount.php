<?php

namespace App\Actions\Accounts;

use App\Models\AuditEvent;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateTenantAccount
{
    public function handle(
        User $actor,
        Tenant $tenant,
        array $input
    ): User {
        // อ่านสถานะล่าสุด ไม่เชื่อข้อมูลบทบาทจากหน้าจอ
        $actor = $actor->fresh();

        abort_unless(
            $actor &&
            $actor->is_active &&
            ! $actor->must_change_password &&
            $actor->u_role === 'admin',
            403
        );

        $validated = Validator::make([
            'u_username' => Str::lower(trim(
                (string) ($input['u_username'] ?? '')
            )),
            'password' => $input['password'] ?? null,
            'password_confirmation' => $input['password_confirmation'] ?? null,
        ], [
            'u_username' => [
                'required',
                'string',
                'min:3',
                'max:45',
                'regex:/\A[a-z0-9._-]+\z/',
                'unique:users,u_username',
            ],
            'password' => [
                'required',
                'string',
                'min:12',
                'confirmed',
            ],
        ])->validate();

        if (strlen($validated['password']) > 72) {
            throw ValidationException::withMessages([
                'password' => 'รหัสผ่านต้องไม่เกิน 72 ไบต์',
            ]);
        }

        return DB::transaction(function () use ($actor, $tenant, $validated) {
            $tenant = Tenant::findOrFail($tenant->getKey());

            if (User::withTrashed()
                ->where('tenants_t_id', $tenant->getKey())
                ->exists()) {
                throw ValidationException::withMessages([
                    'tenants_t_id' => 'ผู้เช่ารายนี้มีบัญชีอยู่แล้ว',
                ]);
            }

            $user = new User();
            $user->u_username = $validated['u_username'];
            $user->u_password = $validated['password'];
            $user->u_role = 'tenant';
            $user->tenants_t_id = $tenant->getKey();
            $user->is_active = true;
            $user->must_change_password = true;
            $user->save();

            AuditEvent::create([
                'actor_user_id' => $actor->getKey(),
                'entity_type' => 'users',
                'entity_id' => $user->getKey(),
                'action' => 'tenant_account_created',
                'new_values' => [
                    'u_username' => $user->u_username,
                    'u_role' => $user->u_role,
                    'tenants_t_id' => $user->tenants_t_id,
                    'is_active' => $user->is_active,
                    'must_change_password' => $user->must_change_password,
                ],
            ]);

            return $user;
        });
    }
}