<?php

use App\Actions\Repairs\CreateRepair;
use App\Actions\Repairs\UpdateRepairStatus;
use App\Actions\Tenants\CreateTenant;
use App\Models\AuditEvent;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

test('history names are read views and every repair transition writes one Audit row', function () {
    $objects = collect(DB::select("SELECT name,type FROM sqlite_master WHERE name IN ('audit_events','payment_events','repair_histories')"))->pluck('type', 'name');
    expect($objects['audit_events'])->toBe('table')->and($objects['payment_events'])->toBe('view')->and($objects['repair_histories'])->toBe('view');
    $admin = User::factory()->create(['u_role' => 'admin', 'is_active' => true, 'must_change_password' => false]);
    $repair = app(CreateRepair::class)->handle($admin, ['rp_name' => 'ไฟทางเดิน', 'rp_description' => 'ชั้น 1', 'rp_type' => 'COMMON']);
    expect(AuditEvent::count())->toBe(1)->and($repair->histories()->count())->toBe(1);
    app(UpdateRepairStatus::class)->handle($admin, $repair, ['expected_status' => 'REPORTED', 'rp_status' => 'IN_PROGRESS']);
    expect(AuditEvent::count())->toBe(2)->and($repair->histories()->count())->toBe(2)
        ->and(AuditEvent::orderByDesc('ae_id')->first()->old_values['rp_status'])->toBe('REPORTED');
    expect(fn () => DB::table('audit_events')->where('entity_type', 'repairs')->update(['action' => 'tampered']))->toThrow(QueryException::class, 'Audit events cannot be updated');
    expect(fn () => DB::table('repair_histories')->delete())->toThrow(QueryException::class, 'Repair histories cannot be deleted');
});

test('new tenant account and audit use current UTC instant while business dates remain Thai', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-15 15:45:00', 'Asia/Bangkok'));
    try {
        $admin = User::factory()->create(['u_role' => 'admin', 'is_active' => true, 'must_change_password' => false]);
        $tenant = app(CreateTenant::class)->handle($admin, ['t_Fname' => 'ณัฐ', 't_Lname' => 'ใจดี', 't_tel' => '0800000040']);
        expect(DB::table('tenants')->where('t_id', $tenant->getKey())->value('created_at'))->toBe('2026-10-15 08:45:00')
            ->and($tenant->created_at->timezone('Asia/Bangkok')->format('Y-m-d H:i:s'))->toBe('2026-10-15 15:45:00')
            ->and($tenant->user->created_at->format('Y-m-d H:i:s'))->toBe('2026-10-15 08:45:00');
        expect(AuditEvent::where('entity_type', 'tenants')->first()->created_at->format('Y-m-d H:i:s'))->toBe('2026-10-15 08:45:00');
    } finally {
        $this->travelBack();
    }
});
