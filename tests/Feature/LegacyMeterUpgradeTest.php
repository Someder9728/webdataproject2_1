<?php

use App\Models\Meter;
use App\Models\Room;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->room = Room::create(['r_name' => 'LEGACY', 'r_floor' => 1, 'r_type' => 'STANDARD', 'r_rent' => 3000, 'r_status' => 'VACANT']);
    Schema::table('meters', function (Blueprint $table) {
        $table->dropUnique('meters_room_date_unique');
        $table->unique(['rooms_r_id', 'm_date', 'm_type'], 'meter_unique_room_date_type');
    });
    $this->migration = require database_path('migrations/2026_10_04_130000_align_legacy_meter_uniqueness.php');
    $this->legacy = ['rooms_r_id' => $this->room->getKey(), 'm_date' => '2026-09-30', 'm_water' => '100.20', 'm_elec' => '200.30', 'm_type' => 'movein'];
});

test('legacy upgrade preserves readings and permits recording without type', function () {
    DB::table('meters')->insert($this->legacy);
    $this->migration->up();
    expect(Schema::hasIndex('meters', 'meters_room_date_unique'))->toBeTrue()
        ->and(Schema::hasIndex('meters', 'meter_unique_room_date_type'))->toBeFalse();
    $this->assertDatabaseHas('meters', $this->legacy);
    Meter::create(['rooms_r_id' => $this->room->getKey(), 'm_date' => '2026-10-01', 'm_water' => '100.40', 'm_elec' => '200.60']);
    $this->assertDatabaseCount('meters', 2);
});

test('legacy upgrade refuses duplicate days without deleting data or indexes', function () {
    DB::table('meters')->insert([$this->legacy, [...$this->legacy, 'm_type' => 'monthly']]);
    expect(fn () => $this->migration->up())->toThrow(RuntimeException::class, 'Resolve duplicate');
    $this->assertDatabaseCount('meters', 2);
    expect(Schema::hasIndex('meters', 'meter_unique_room_date_type'))->toBeTrue()
        ->and(Schema::hasIndex('meters', 'meters_room_date_unique'))->toBeFalse();
});
