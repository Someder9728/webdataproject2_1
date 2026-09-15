<?php

namespace App\Actions\Accounts;

use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SuspendAccount
{
    public function handle(
        User $actor,
        User $target,
        ?string $reason = null
    ): void {
        $validated = Validator::make(
            ['reason' => $reason],
            ['reason' => ['nullable', 'string', 'max:2000']]
        )->validate();

        DB::transaction(function () use ($actor, $target, $validated) {
            // อ่านสถานะล่าสุดภายใน Transaction
            $actor = User::find($actor->getKey());

            abort_unless(
                $actor &&
                $actor->is_active &&
                ! $actor->must_change_password &&
                $actor->u_role === 'admin',
                403
            );

            $target = User::findOrFail($target->getKey());

            if (! $target->is_active) {
                abort(409, 'บัญชีนี้ถูกระงับแล้ว');
            }

            if ($target->u_role === 'admin') {
                $otherActiveAdminExists = User::where('u_role', 'admin')
                    ->where('is_active', true)
                    ->where('u_id', '!=', $target->getKey())
                    ->exists();

                abort_unless(
                    $otherActiveAdminExists,
                    409,
                    'ไม่สามารถระงับ Admin ที่ใช้งานได้คนสุดท้าย'
                );
            }

            $target->is_active = false;
            $target->remember_token = Str::random(60);
            $target->save();

            DB::table('sessions')
                ->where('user_id', $target->getKey())
                ->delete();

            AuditEvent::create([
                'actor_user_id' => $actor->getKey(),
                'entity_type' => 'users',
                'entity_id' => $target->getKey(),
                'action' => 'account_suspended',
                'old_values' => ['is_active' => true],
                'new_values' => ['is_active' => false],
                'reason' => $validated['reason'] ?? null,
            ]);
        }, 3);
    }
}