<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('meters', function (Blueprint $table) {
            $table->enum('m_type', [
                'move_in',
                'monthly',
                'move_out'
            ])->after('m_date');

            $table->unique([
                'rooms_r_id',
                'm_date',
                'm_type'
            ], 'meter_unique_room_date_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meters', function (Blueprint $table) {
            $table->dropUnique('meter_unique_room_date_type');
            $table->dropColumn('m_type');
        });
    }
};