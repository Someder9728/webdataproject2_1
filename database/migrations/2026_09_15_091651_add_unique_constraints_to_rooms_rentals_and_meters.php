<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->unique('r_name', 'rooms_r_name_unique');
        });

        Schema::table('meters', function (Blueprint $table) {
            $table->unique(
                ['rooms_r_id', 'm_date'],
                'meters_room_date_unique'
            );
        });

        DB::statement("
            CREATE UNIQUE INDEX rentals_active_tenant_unique
            ON rentals (tenants_t_id)
            WHERE rt_status = 'ACTIVE' AND deleted_at IS NULL
        ");

        DB::statement("
            CREATE UNIQUE INDEX rentals_active_room_unique
            ON rentals (rooms_r_id)
            WHERE rt_status = 'ACTIVE' AND deleted_at IS NULL
        ");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX rentals_active_room_unique');
        DB::statement('DROP INDEX rentals_active_tenant_unique');

        Schema::table('meters', function (Blueprint $table) {
            $table->dropUnique('meters_room_date_unique');
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->dropUnique('rooms_r_name_unique');
        });
    }
};