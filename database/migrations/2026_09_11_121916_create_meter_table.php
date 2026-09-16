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
        Schema::create('meters', function (Blueprint $table) {
            $table->id('m_id');
            $table->date('m_date');
            $table->decimal('m_water', 10, 2);
            $table->decimal('m_elec', 10, 2);
            $table->foreignId('rooms_r_id')
                ->constrained('rooms', 'r_id');


            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meters');
    }
};
