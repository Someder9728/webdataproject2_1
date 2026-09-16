<?php

use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->actor = User::factory()->create();

    $this->event = AuditEvent::create([
        'actor_user_id' => $this->actor->getKey(),
        'entity_type' => 'users',
        'entity_id' => $this->actor->getKey(),
        'action' => 'account_updated',
        'old_values' => ['is_active' => true],
        'new_values' => ['is_active' => false],
        'reason' => 'ข้อมูลสำหรับทดสอบ Audit',
    ]);
});

test('audit stores values and links to actor', function () {
    $event = $this->event->fresh();

    expect($event->old_values)->toBe(['is_active' => true])
        ->and($event->new_values)->toBe(['is_active' => false])
        ->and($event->actor->getKey())->toBe($this->actor->getKey())
        ->and($event->created_at)->not->toBeNull();
});

test('database rejects audit update', function () {
    expect(fn () => DB::table('audit_events')
        ->where('ae_id', $this->event->getKey())
        ->update(['reason' => 'พยายามแก้ประวัติ'])
    )->toThrow(QueryException::class, 'Audit events cannot be updated');

    $this->assertDatabaseHas('audit_events', [
        'ae_id' => $this->event->getKey(),
        'reason' => 'ข้อมูลสำหรับทดสอบ Audit',
    ]);
});

test('database rejects audit deletion', function () {
    expect(fn () => DB::table('audit_events')
        ->where('ae_id', $this->event->getKey())
        ->delete()
    )->toThrow(QueryException::class, 'Audit events cannot be deleted');

    $this->assertDatabaseHas('audit_events', [
        'ae_id' => $this->event->getKey(),
    ]);
});

test('system audit can have no actor', function () {
    $event = AuditEvent::create([
        'actor_user_id' => null,
        'entity_type' => 'users',
        'entity_id' => $this->actor->getKey(),
        'action' => 'system_check',
    ]);

    expect($event->fresh()->actor)->toBeNull();
});