<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {

            $table->id('u_id');

            $table->string('u_username', 45)->unique();
            $table->string('u_password', 255);

            $table->string('u_role', 10);

            $table->foreignId('tenants_t_id')
                ->nullable()
                ->unique()
                ->constrained('tenants', 't_id')
                ->nullOnDelete();

            $table->boolean('is_active')->default(true);
            $table->boolean('must_change_password')->default(true);

            $table->rememberToken();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};