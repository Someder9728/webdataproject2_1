<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
      Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->renameColumn('r_number', 'r_name');
            $table->renameColumn('r_price', 'r_rent');
        });
    }

    /*
      Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->renameColumn('r_name', 'r_number');
            $table->renameColumn('r_rent', 'r_price');
        });
    }
};