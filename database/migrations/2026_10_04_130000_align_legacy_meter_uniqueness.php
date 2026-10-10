<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('meters')->select('rooms_r_id')
            ->groupBy('rooms_r_id')->groupByRaw('date(m_date)')
            ->havingRaw('count(*) > 1')->exists()) {
            throw new RuntimeException('Resolve duplicate room/day meter readings before migration. No readings have been deleted.');
        }

        if (! Schema::hasIndex('meters', 'meters_room_date_unique')) {
            Schema::table('meters', fn (Blueprint $table) => $table->unique(['rooms_r_id', 'm_date'], 'meters_room_date_unique'));
        }

        if (Schema::hasIndex('meters', 'meter_unique_room_date_type')) {
            Schema::table('meters', fn (Blueprint $table) => $table->dropUnique('meter_unique_room_date_type'));
        }

        // Preserve legacy values, but the API no longer requires or uses m_type.
        if (Schema::hasColumn('meters', 'm_type')) {
            Schema::table('meters', fn (Blueprint $table) => $table->string('m_type')->nullable()->default('monthly')->change());
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('meters', 'm_type') && ! Schema::hasIndex('meters', 'meter_unique_room_date_type')) {
            Schema::table('meters', fn (Blueprint $table) => $table->unique(['rooms_r_id', 'm_date', 'm_type'], 'meter_unique_room_date_type'));
        }

        if (Schema::hasIndex('meters', 'meters_room_date_unique')) {
            Schema::table('meters', fn (Blueprint $table) => $table->dropUnique('meters_room_date_unique'));
        }
    }
};
