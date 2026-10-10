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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id('i_id');
            $table->date('i_date');
            $table->decimal('i_rent', 10, 2);
            $table->decimal('i_water', 10, 2);
            $table->decimal('i_elec', 10, 2);
            $table->decimal('i_total', 10, 2);
            $table->date('i_due');
            $table->foreignId('rentals_rt_id')
                ->constrained('rentals', 'rt_id');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
