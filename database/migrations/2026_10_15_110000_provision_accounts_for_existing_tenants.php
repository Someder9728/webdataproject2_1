<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $orphans = DB::table('tenants')->whereNull('deleted_at')->whereNotExists(function ($query) {
                $query->selectRaw('1')->from('users')->whereColumn('users.tenants_t_id', 'tenants.t_id');
            })->get();
            foreach ($orphans as $tenant) {
                $username = 'tenant_legacy_'.$tenant->t_id.'_'.Str::lower(Str::random(6));
                $id = DB::table('users')->insertGetId(['u_username' => $username, 'u_password' => Hash::make(Str::random(32)),
                    'u_role' => 'tenant', 'tenants_t_id' => $tenant->t_id, 'is_active' => true, 'must_change_password' => true,
                    'created_at' => now(), 'updated_at' => now()], 'u_id');
                DB::table('audit_events')->insert(['actor_user_id' => null, 'entity_type' => 'users', 'entity_id' => $id,
                    'action' => 'tenant_account_backfilled', 'new_values' => json_encode(['u_username' => $username, 'tenants_t_id' => $tenant->t_id]),
                    'reason' => 'TASK-02 migration ผู้ดูแลต้องตั้งรหัสผ่านก่อนใช้งาน', 'created_at' => now()]);
            }
        });
    }

    public function down(): void
    {
        // Accounts and their immutable audit records are historical data; preserve them.
    }
};
