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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id('c_id');
            $table->string('c_number')->unique();
            $table->date('c_start');
            $table->date('c_end')->nullable();

            $table->decimal('c_rent', 10, 2);
            $table->decimal('c_deposit', 10, 2);

            $table->string('c_status');

            $table->foreignId('rentals_rt_id')
                ->unique()
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
        Schema::dropIfExists('contracts');
    }
};
