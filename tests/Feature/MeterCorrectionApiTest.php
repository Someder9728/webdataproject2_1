<?php

use App\Actions\Billing\IssueInvoice;
use App\Actions\Meters\CorrectMeterReading;
use App\Actions\Meters\RecordMeterReading;
use App\Models\AuditEvent;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Rental;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use App\Support\MeterMutationRules;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-10 12:00:00', 'Asia/Bangkok'));
    config(['dormitory.water_rate' => '18.00', 'dormitory.elec_rate' => '7.00']);
    $this->admin = User::factory()->create(['must_change_password' => false]);
    $this->room = Room::create(['r_name' => 'CORRECT-1', 'r_floor' => 1, 'r_type' => 'เตียงเดี่ยว', 'r_rent' => '3000.00', 'r_status' => 'VACANT']);
    foreach ([['2026-09-01', '10.00', '100.00'], ['2026-09-15', '20.00', '200.00'], ['2026-10-01', '30.00', '300.00']] as [$date, $water, $elec]) {
        app(RecordMeterReading::class)->handle($this->admin, $this->room, ['m_date' => $date, 'm_water' => $water, 'm_elec' => $elec]);
    }
    $this->meter = Meter::whereDate('m_date', '2026-09-15')->sole();
    $this->url = '/api/v1/rooms/'.$this->room->getKey().'/meters/'.$this->meter->getKey();
    $this->payload = ['m_date' => '2026-09-15', 'm_water' => '21.00', 'm_elec' => '210.00', 'reason' => 'Correct typing mistake', 'confirmed' => true, 'expected_event_id' => MeterMutationRules::latestEventId($this->meter)];
});

afterEach(function () {
    $this->travelBack();
});

test('unused reading corrects with confirmation and before after audit', function () {
    $this->actingAs($this->admin)->patchJson($this->url, $this->payload)->assertOk()->assertJsonPath('data.m_water', '21.00');
    $audit = AuditEvent::where('action', 'meter_reading_corrected')->sole();
    expect($audit->old_values['m_water'])->toBe('20.00');
    expect($audit->new_values['m_water'])->toBe('21.00');
    expect($audit->reason)->toBe('Correct typing mistake');
    $this->actingAs($this->admin)->patchJson($this->url, [...$this->payload, 'm_water' => '22.00'])->assertConflict();
});

test('delete unused mistake permits recording again on same date while retaining audit', function () {
    $this->actingAs($this->admin)->deleteJson($this->url, [...$this->payload, 'confirmed' => false])->assertUnprocessable()->assertJsonValidationErrors('confirmed');
    $this->actingAs($this->admin)->deleteJson($this->url, $this->payload)->assertOk();
    expect(Meter::withTrashed()->findOrFail($this->meter->getKey())->trashed())->toBeTrue();
    expect(AuditEvent::where('action', 'meter_reading_cancelled')->sole()->old_values['m_date'])->toBe('2026-09-15');
    $this->actingAs($this->admin)->getJson('/api/v1/rooms/'.$this->room->getKey().'/meters?include_cancelled=1')
        ->assertOk()->assertJsonCount(3, 'data')->assertJsonPath('data.1.status', 'CANCELLED')->assertJsonPath('data.1.can_edit', false);
    $this->actingAs($this->admin)->getJson('/api/v1/rooms/'.$this->room->getKey().'/meters')->assertJsonCount(2, 'data');
    $this->actingAs($this->admin)->postJson('/api/v1/rooms/'.$this->room->getKey().'/meters', ['m_date' => '2026-09-15', 'm_water' => '22.00', 'm_elec' => '220.00'])->assertCreated();
    expect(AuditEvent::where('action', 'meter_reading_replaced')->sole()->old_values['m_water'])->toBe('20.00');
    expect($this->meter->fresh()->m_water)->toBe('22.00');
});

test('cancelled boundary cannot issue invoice until replacement is recorded on exact date', function () {
    $tenant = Tenant::create(['t_Fname' => 'Boundary', 't_Lname' => 'Tenant', 't_tel' => '0812345678']);
    $rental = Rental::create(['tenants_t_id' => $tenant->getKey(), 'rooms_r_id' => $this->room->getKey(), 'rt_movein' => '2026-09-01', 'rt_status' => 'ACTIVE']);
    $rental->contract()->create(['c_number' => 'BOUNDARY-REPLACEMENT', 'c_start' => '2026-09-01', 'c_end' => null, 'c_rent' => '3000.00', 'c_deposit' => '6000.00', 'c_status' => 'ACTIVE']);
    $this->actingAs($this->admin)->deleteJson($this->url, $this->payload)->assertOk();
    $input = ['rentals_rt_id' => $rental->getKey(), 'period_start' => '2026-09-01', 'period_end' => '2026-09-15'];
    try {
        app(IssueInvoice::class)->handle($this->admin, $input, persist: true);
        $this->fail('Cancelled boundary accepted');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('period_end');
    }
    expect(Invoice::count())->toBe(0);
    $opening = Meter::whereDate('m_date', '2026-09-01')->sole();
    $this->actingAs($this->admin)->getJson('/api/v1/rooms/'.$this->room->getKey().'/meter-usage?start_meter_id='.$opening->getKey().'&end_meter_id='.$this->meter->getKey())->assertNotFound();
    app(RecordMeterReading::class)->handle($this->admin, $this->room, ['m_date' => '2026-09-15', 'm_water' => '22.00', 'm_elec' => '220.00']);
    $snapshot = app(IssueInvoice::class)->handle($this->admin, $input, persist: true);
    expect($snapshot['water_usage'])->toBe('12.00');
    expect($snapshot['elec_usage'])->toBe('120.00');
    $this->actingAs($this->admin)->patchJson($this->url, [...$this->payload, 'expected_event_id' => MeterMutationRules::latestEventId($this->meter)])->assertConflict();
});

test('correction requires csrf evidence and valid confirmation', function () {
    $this->app->bind(PreventRequestForgery::class, function ($app) {
        return new class($app, $app->make(Encrypter::class)) extends PreventRequestForgery
        {
            protected function runningUnitTests()
            {
                return false;
            }
        };
    });
    $token = str_repeat('a', 40);
    $this->actingAs($this->admin)->withSession(['_token' => $token])->patchJson($this->url, $this->payload)->assertStatus(419);
    $this->actingAs($this->admin)->withSession(['_token' => $token])->deleteJson($this->url, $this->payload)->assertStatus(419);
    $this->actingAs($this->admin)->withSession(['_token' => $token])->patchJson($this->url, $this->payload, ['X-CSRF-TOKEN' => $token])->assertOk();
});

test('meter UI renders correction form and guest cannot mutate', function () {
    $this->patchJson($this->url, $this->payload)->assertUnauthorized();
    $this->actingAs($this->admin)->get('/meters')->assertOk()->assertSee('meter-edit-form', false);
});

test('active rental opening and unbilled ending reading can correct', function () {
    $tenant = Tenant::create(['t_Fname' => 'Meter', 't_Lname' => 'Tenant', 't_tel' => '0812345678']);
    $rental = Rental::create(['tenants_t_id' => $tenant->getKey(), 'rooms_r_id' => $this->room->getKey(), 'rt_movein' => '2026-09-01', 'rt_status' => 'ACTIVE']);
    $opening = Meter::whereDate('m_date', '2026-09-01')->sole();
    $openingUrl = '/api/v1/rooms/'.$this->room->getKey().'/meters/'.$opening->getKey();
    $this->actingAs($this->admin)->patchJson($openingUrl, [...$this->payload, 'm_date' => '2026-09-01', 'm_water' => '11.00', 'm_elec' => '110.00', 'expected_event_id' => MeterMutationRules::latestEventId($opening)])->assertOk();
    $this->actingAs($this->admin)->deleteJson($openingUrl, [...$this->payload, 'expected_event_id' => MeterMutationRules::latestEventId($opening)])->assertConflict();
    $this->actingAs($this->admin)->patchJson($this->url, $this->payload)->assertOk();
    $rental->update(['rt_status' => 'ENDED', 'rt_moveout' => '2026-09-15']);
    $this->actingAs($this->admin)->deleteJson($this->url, [...$this->payload, 'expected_event_id' => MeterMutationRules::latestEventId($this->meter)])->assertConflict();
});

test('invoice referenced ending locks including soft deleted invoice', function () {
    $tenant = Tenant::create(['t_Fname' => 'Billed', 't_Lname' => 'Tenant', 't_tel' => '0812345678']);
    $rental = Rental::create(['tenants_t_id' => $tenant->getKey(), 'rooms_r_id' => $this->room->getKey(), 'rt_movein' => '2026-09-01', 'rt_status' => 'ACTIVE']);
    $rental->contract()->create(['c_number' => 'BILLED-CORRECTION', 'c_start' => '2026-09-01', 'c_end' => null, 'c_rent' => '3000.00', 'c_deposit' => '6000.00', 'c_status' => 'ACTIVE']);
    $invoice = app(IssueInvoice::class)->handle($this->admin, ['rentals_rt_id' => $rental->getKey(), 'period_start' => '2026-09-01', 'period_end' => '2026-09-15'], persist: true);
    $this->actingAs($this->admin)->patchJson($this->url, $this->payload)->assertConflict();
    Invoice::whereKey($invoice['i_id'])->firstOrFail()->delete();
    $this->actingAs($this->admin)->deleteJson($this->url, $this->payload)->assertConflict();
    expect($this->meter->fresh()->m_water)->toBe('20.00');
});

test('invalid corrections do not change reading', function (array $changes, string $field) {
    $this->actingAs($this->admin)->patchJson($this->url, array_replace($this->payload, $changes))->assertUnprocessable()->assertJsonValidationErrors($field);
    expect($this->meter->fresh()->m_water)->toBe('20.00');
})->with([
    [['m_water' => '9.00'], 'm_water'], [['m_elec' => '301.00'], 'm_elec'], [['reason' => ' '], 'reason'], [['confirmed' => false], 'confirmed'], [['m_date' => 'invalid'], 'm_date'],
]);

test('duplicate dates wrong room and permissions cannot mutate', function () {
    $this->actingAs($this->admin)->patchJson($this->url, [...$this->payload, 'm_date' => '2026-10-01'])->assertConflict();
    $other = Room::create(['r_name' => 'CORRECT-2', 'r_floor' => 1, 'r_type' => 'เตียงเดี่ยว', 'r_rent' => '3000.00', 'r_status' => 'VACANT']);
    $this->actingAs($this->admin)->patchJson('/api/v1/rooms/'.$other->getKey().'/meters/'.$this->meter->getKey(), $this->payload)->assertNotFound();
    $user = User::factory()->create(['u_role' => 'tenant', 'must_change_password' => false]);
    $this->actingAs($user)->patchJson($this->url, $this->payload)->assertForbidden();
    $this->actingAs($user)->deleteJson($this->url, $this->payload)->assertForbidden();
});

test('audit failure rolls back cancellation and action rechecks stale actor', function () {
    $eventName = 'eloquent.creating: '.AuditEvent::class;
    $dispatcher = AuditEvent::getEventDispatcher();
    $listeners = $dispatcher->getRawListeners()[$eventName] ?? [];
    $dispatcher->listen($eventName, function (AuditEvent $audit) {
        if ($audit->action === 'meter_reading_cancelled') {
            throw new RuntimeException('Audit failed');
        }
    });
    try {
        app(CorrectMeterReading::class)->handle($this->admin, $this->room, $this->meter, $this->payload, delete: true);
        $this->fail('Expected audit failure');
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('Audit failed');
    } finally {
        $dispatcher->forget($eventName);
        foreach ($listeners as $listener) {
            $dispatcher->listen($eventName, $listener);
        }
    }
    expect(Meter::whereKey($this->meter->getKey())->exists())->toBeTrue();
    User::whereKey($this->admin->getKey())->update(['is_active' => false]);
    try {
        app(CorrectMeterReading::class)->handle($this->admin, $this->room, $this->meter, $this->payload);
        $this->fail('Stale inactive admin allowed');
    } catch (HttpExceptionInterface $exception) {
        expect($exception->getStatusCode())->toBe(403);
    }
});
