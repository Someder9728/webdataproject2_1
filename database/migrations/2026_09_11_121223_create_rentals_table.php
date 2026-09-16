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
        Schema::create('rentals', function (Blueprint $table) {
            $table->id('rt_id');
            $table->date('rt_movein');
            $table->date('rt_moveout')->nullable();
            $table->string('rt_status');

            $table->foreignId('rooms_r_id')
                ->constrained('rooms', 'r_id');

            $table->foreignId('tenants_t_id')
                ->constrained('tenants', 't_id');


            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rentals');
    }
};
