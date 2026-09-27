<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id('r_id');

            $table->string('r_number', 20)->unique();

            $table->integer('r_floor');

            $table->string('r_type', 100);

            $table->decimal('r_price', 10, 2);

            $table->string('r_status', 30)->default('ว่าง');

            $table->timestamps();

            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};