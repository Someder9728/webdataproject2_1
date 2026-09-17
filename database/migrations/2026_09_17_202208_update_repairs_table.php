<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repairs', function (Blueprint $table) {

           
            $table->dropForeign(['tenants_t_id']);
  
            $table->dropColumn('tenants_t_id');
            
            $table->foreignId('reported_by_user_id')
                ->after('rp_type')
                ->constrained('users', 'u_id')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('repairs', function (Blueprint $table) {

            
            $table->dropForeign(['reported_by_user_id']);
            $table->dropColumn('reported_by_user_id');

            
            $table->foreignId('tenants_t_id')
                ->after('rp_type')
                ->constrained('tenants', 't_id');
        });
    }
};