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
        Schema::create('repairs', function (Blueprint $table) {
             $table->id('rp_id');
            $table->string('rp_name');
            $table->string('rp_description')->nullable();
            $table->string('rp_status');
            $table->string('rp_type');
            $table->foreignId('tenants_t_id')
                ->constrained('tenants', 't_id');
            $table->foreignId('rooms_r_id')
                ->nullable()
                ->constrained('rooms', 'r_id')
                ->nullOnDelete();


            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repairs');
    }
};
