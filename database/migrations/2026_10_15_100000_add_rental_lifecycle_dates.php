<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP INDEX rentals_active_room_unique');
        DB::statement('DROP INDEX rentals_active_tenant_unique');
        Schema::table('rentals', function (Blueprint $table) {
            $table->date('rt_movein')->nullable()->change();
            $table->date('scheduled_movein_at')->nullable();
            $table->date('scheduled_moveout_at')->nullable();
        });
        DB::statement("CREATE UNIQUE INDEX rentals_active_room_unique ON rentals (rooms_r_id) WHERE rt_status = 'ACTIVE' AND deleted_at IS NULL");
        DB::statement("CREATE UNIQUE INDEX rentals_active_tenant_unique ON rentals (tenants_t_id) WHERE rt_status = 'ACTIVE' AND deleted_at IS NULL");
        DB::table('rentals')->update(['scheduled_movein_at' => DB::raw('rt_movein')]);
    }

    public function down(): void
    {
        if (DB::table('rentals')->whereNull('rt_movein')->exists()) {
            throw new RuntimeException('Cannot roll back while unactivated reservations exist.');
        }
        DB::statement('DROP INDEX rentals_active_room_unique');
        DB::statement('DROP INDEX rentals_active_tenant_unique');
        Schema::table('rentals', function (Blueprint $table) {
            $table->dropColumn(['scheduled_movein_at', 'scheduled_moveout_at']);
            $table->date('rt_movein')->nullable(false)->change();
        });
        DB::statement("CREATE UNIQUE INDEX rentals_active_room_unique ON rentals (rooms_r_id) WHERE rt_status = 'ACTIVE' AND deleted_at IS NULL");
        DB::statement("CREATE UNIQUE INDEX rentals_active_tenant_unique ON rentals (tenants_t_id) WHERE rt_status = 'ACTIVE' AND deleted_at IS NULL");
    }
};
