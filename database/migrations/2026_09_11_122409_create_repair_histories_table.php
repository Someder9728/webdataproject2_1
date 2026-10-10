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
        Schema::create('repair_histories', function (Blueprint $table) {
            $table->id('rph_id');
            $table->string('rph_name')->nullable();
            $table->string('rph_description')->nullable();
            $table->string('rph_status');
            $table->string('rph_type')->nullable();
            $table->foreignId('repairs_rp_id')
                ->constrained('repairs', 'rp_id');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repair_histories');
    }
};
