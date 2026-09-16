<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // บิลเก่าต้องมีข้อมูลจริงสำหรับรอบบิลและมิเตอร์ก่อนเพิ่มฟิลด์บังคับ
        if (DB::table('invoices')->exists()) {
            throw new RuntimeException(
                'Existing invoices require a billing-data backfill before this migration.'
            );
        }

        Schema::table('invoices', function (Blueprint $table) {
            $table->date('period_start');
            $table->date('period_end');

            $table->unsignedBigInteger('start_meter_id');
            $table->unsignedBigInteger('end_meter_id');

            $table->decimal('water_usage', 10, 2);
            $table->decimal('elec_usage', 10, 2);
            $table->decimal('water_rate', 10, 2);
            $table->decimal('elec_rate', 10, 2);
            $table->decimal('rent_rate', 10, 2);

            $table->foreign('start_meter_id')
                ->references('m_id')
                ->on('meters')
                ->restrictOnDelete();

            $table->foreign('end_meter_id')
                ->references('m_id')
                ->on('meters')
                ->restrictOnDelete();

            $table->unique(
                ['rentals_rt_id', 'period_start', 'period_end'],
                'invoices_rental_period_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('invoices_rental_period_unique');

            $table->dropForeign(['start_meter_id']);
            $table->dropForeign(['end_meter_id']);

            $table->dropColumn([
                'period_start',
                'period_end',
                'start_meter_id',
                'end_meter_id',
                'water_usage',
                'elec_usage',
                'water_rate',
                'elec_rate',
                'rent_rate',
            ]);
        });
    }
};