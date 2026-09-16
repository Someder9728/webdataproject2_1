<?php

use App\Models\Repair;
use App\Models\RepairHistory;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->actor = User::factory()->create();

    $this->room = Room::create([
        'r_name' => 'REPAIR-101',
        'r_floor' => 1,
        'r_type' => 'STANDARD',
        'r_rent' => 3000,
        'r_status' => 'VACANT',
    ]);

    $this->repairData = [
        'rp_name' => 'Hallway light',
        'rp_description' => str_repeat('Repair details. ', 100),
        'rp_status' => 'REPORTED',
        'rp_type' => 'COMMON',
        'tenants_t_id' => null,
        'rooms_r_id' => null,
        'reported_by_user_id' => $this->actor->getKey(),
    ];
});

test('common repair can store reporter without tenant or room', function () {
    $repair = Repair::create($this->repairData)->fresh();

    expect($repair->tenants_t_id)->toBeNull()
        ->and($repair->rooms_r_id)->toBeNull()
        ->and($repair->rp_description)
        ->toBe($this->repairData['rp_description'])
        ->and($repair->reporter->getKey())
        ->toBe($this->actor->getKey());
});

test('room repair can reference a room', function () {
    $repair = Repair::create(array_replace($this->repairData, [
        'rp_type' => 'ROOM',
        'rooms_r_id' => $this->room->getKey(),
    ]))->fresh();

    expect($repair->room->getKey())->toBe($this->room->getKey());
});

test('database rejects invalid repair assignments on insert', function (
    string $type,
    bool $withRoom
) {
    $data = array_replace($this->repairData, [
        'rp_type' => $type,
        'rooms_r_id' => $withRoom ? $this->room->getKey() : null,
    ]);

    // เขียนตรงเพื่อยืนยันว่าฐานข้อมูลบังคับกฎเอง
    expect(fn () => DB::table('repairs')->insert($data))
        ->toThrow(
            QueryException::class,
            'Invalid repair type and room assignment'
        );
})->with([
    'room without room id' => ['ROOM', false],
    'common with room id' => ['COMMON', true],
    'unknown type' => ['OTHER', false],
]);

test('database rejects invalid repair assignments on update', function (
    string $type,
    bool $withRoom
) {
    $repair = Repair::create($this->repairData);

    expect(fn () => DB::table('repairs')
        ->where('rp_id', $repair->getKey())
        ->update([
            'rp_type' => $type,
            'rooms_r_id' => $withRoom ? $this->room->getKey() : null,
        ])
    )->toThrow(
        QueryException::class,
        'Invalid repair type and room assignment'
    );
})->with([
    'room without room id' => ['ROOM', false],
    'common with room id' => ['COMMON', true],
    'unknown type' => ['OTHER', false],
]);

test('repair rejects nonexistent reporter', function () {
    $missingId = ((int) DB::table('users')->max('u_id')) + 1;

    expect(fn () => Repair::create(array_replace($this->repairData, [
        'reported_by_user_id' => $missingId,
    ])))->toThrow(QueryException::class, 'FOREIGN KEY constraint failed');
});

test('repair requires a reporter', function () {
    $data = $this->repairData;
    unset($data['reported_by_user_id']);

    expect(fn () => DB::table('repairs')->insert($data))
        ->toThrow(QueryException::class, 'NOT NULL constraint failed');
});

test('referenced room cannot be physically deleted', function () {
    Repair::create(array_replace($this->repairData, [
        'rp_type' => 'ROOM',
        'rooms_r_id' => $this->room->getKey(),
    ]));

    expect(fn () => DB::table('rooms')
        ->where('r_id', $this->room->getKey())
        ->delete()
    )->toThrow(QueryException::class, 'FOREIGN KEY constraint failed');
});

test('repair history stores actor and description', function () {
    $repair = Repair::create($this->repairData);
    $description = str_repeat('History details. ', 100);

    $history = RepairHistory::create([
        'repairs_rp_id' => $repair->getKey(),
        'rph_status' => 'REPORTED',
        'rph_description' => $description,
        'changed_by_user_id' => $this->actor->getKey(),
    ])->fresh();

    expect($history->repair->getKey())->toBe($repair->getKey())
        ->and($history->changedBy->getKey())->toBe($this->actor->getKey())
        ->and($history->rph_description)->toBe($description);
});

test('repair history rejects nonexistent actor', function () {
    $repair = Repair::create($this->repairData);
    $missingId = ((int) DB::table('users')->max('u_id')) + 1;

    expect(fn () => RepairHistory::create([
        'repairs_rp_id' => $repair->getKey(),
        'rph_status' => 'REPORTED',
        'changed_by_user_id' => $missingId,
    ]))->toThrow(QueryException::class, 'FOREIGN KEY constraint failed');
});

test('repair history requires an actor', function () {
    $repair = Repair::create($this->repairData);

    expect(fn () => DB::table('repair_histories')->insert([
        'repairs_rp_id' => $repair->getKey(),
        'rph_status' => 'REPORTED',
    ]))->toThrow(QueryException::class, 'NOT NULL constraint failed');
});