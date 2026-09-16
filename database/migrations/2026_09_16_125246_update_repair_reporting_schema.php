<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            DB::table('repairs')->exists() ||
            DB::table('repair_histories')->exists()
        ) {
            throw new RuntimeException(
                'Existing repair records require an actor-data backfill.'
            );
        }

        Schema::table('repairs', function (Blueprint $table) {
            $table->unsignedBigInteger('tenants_t_id')
                ->nullable()
                ->change();

            $table->text('rp_description')
                ->nullable()
                ->change();

            $table->unsignedBigInteger('reported_by_user_id');

            $table->dropForeign(['rooms_r_id']);

            $table->foreign('rooms_r_id')
                ->references('r_id')
                ->on('rooms')
                ->restrictOnDelete();

            $table->foreign('reported_by_user_id')
                ->references('u_id')
                ->on('users')
                ->restrictOnDelete();
        });

        Schema::table('repair_histories', function (Blueprint $table) {
            $table->text('rph_description')
                ->nullable()
                ->change();

            $table->unsignedBigInteger('changed_by_user_id');

            $table->foreign('changed_by_user_id')
                ->references('u_id')
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (
            DB::table('repairs')->exists() ||
            DB::table('repair_histories')->exists()
        ) {
            throw new RuntimeException(
                'Cannot roll back this migration while repair records exist.'
            );
        }

        Schema::table('repair_histories', function (Blueprint $table) {
            $table->dropForeign(['changed_by_user_id']);
            $table->dropColumn('changed_by_user_id');

            $table->string('rph_description')
                ->nullable()
                ->change();
        });

        Schema::table('repairs', function (Blueprint $table) {
            $table->dropForeign(['reported_by_user_id']);
            $table->dropColumn('reported_by_user_id');

            $table->dropForeign(['rooms_r_id']);

            $table->foreign('rooms_r_id')
                ->references('r_id')
                ->on('rooms')
                ->nullOnDelete();

            $table->unsignedBigInteger('tenants_t_id')
                ->nullable(false)
                ->change();

            $table->string('rp_description')
                ->nullable()
                ->change();
        });
    }
};