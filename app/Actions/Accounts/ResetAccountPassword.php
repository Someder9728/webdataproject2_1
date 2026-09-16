<?php

namespace App\Actions\Accounts;

use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ResetAccountPassword
{
    public function handle(User $actor, User $target, array $input): void
    {
        $actor = $actor->fresh();

        abort_unless(
            $actor &&
            $actor->is_active &&
            ! $actor->must_change_password &&
            $actor->u_role === 'admin',
            403
        );

        $validated = Validator::make($input, [
            'password' => ['required', 'string', 'min:12', 'confirmed'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ])->validate();

        if (strlen($validated['password']) > 72) {
            throw ValidationException::withMessages([
                'password' => 'รหัสผ่านต้องไม่เกิน 72 ไบต์',
            ]);
        }

        DB::transaction(function () use ($actor, $target, $validated) {
            $target = User::findOrFail($target->getKey());

            if (Hash::check($validated['password'], $target->u_password)) {
                throw ValidationException::withMessages([
                    'password' => 'รหัสชั่วคราวต้องต่างจากรหัสเดิม',
                ]);
            }

            $oldValues = [
                'must_change_password' => $target->must_change_password,
            ];

            $target->u_password = $validated['password'];
            $target->must_change_password = true;
            $target->remember_token = Str::random(60);
            $target->save();

            DB::table('sessions')
                ->where('user_id', $target->getKey())
                ->delete();

            AuditEvent::create([
                'actor_user_id' => $actor->getKey(),
                'entity_type' => 'users',
                'entity_id' => $target->getKey(),
                'action' => 'account_password_reset',
                'old_values' => $oldValues,
                'new_values' => [
                    'must_change_password' => true,
                ],
                'reason' => $validated['reason'] ?? null,
            ]);
        });
    }
}