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
        // m_type มีอยู่แล้วตั้งแต่ create_meter_table (ไม่ต้องเพิ่มซ้ำ)
        // เหลือแค่เปลี่ยน unique จาก (room+date) เป็น (room+date+type)
        Schema::table('meters', function (Blueprint $table) {
            $table->dropUnique('meters_room_date_unique');

            $table->unique(
                ['rooms_r_id', 'm_date', 'm_type'],
                'meter_unique_room_date_type'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meters', function (Blueprint $table) {
            $table->dropUnique('meter_unique_room_date_type');

            $table->unique(
                ['rooms_r_id', 'm_date'],
                'meters_room_date_unique'
            );
        });
    }
};
