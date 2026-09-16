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
        Schema::create('payments', function (Blueprint $table) {
            $table->id('p_id');
            $table->date('p_date')->nullable();
            $table->decimal('p_amount', 10, 2);
            $table->string('p_type')->nullable();
            $table->string('p_status');
            $table->string('p_proof')->nullable();
            $table->string('p_reject_reason')->nullable();
            $table->foreignId('invoices_i_id')
                ->unique()
                ->constrained('invoices', 'i_id');

            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
