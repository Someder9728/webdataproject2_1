<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            throw new RuntimeException(
                'This migration requires SQLite.'
            );
        }

        Schema::create('payment_events', function (Blueprint $table) {
            $table->id('pe_id');

            $table->foreignId('payments_p_id')
                ->constrained('payments', 'p_id')
                ->restrictOnDelete();

            $table->foreignId('actor_user_id')
                ->constrained('users', 'u_id')
                ->restrictOnDelete();

            $table->string('event_type', 50);
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);

            $table->decimal('amount', 10, 2);
            $table->date('payment_date')->nullable();
            $table->string('method', 20)->nullable();

            $table->text('proof_path')->nullable();
            $table->text('reason')->nullable();
            $table->text('note')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(
                ['payments_p_id', 'created_at', 'pe_id'],
                'payment_events_payment_timeline_index'
            );

            $table->index('actor_user_id');
        });

        DB::unprepared("
            CREATE TRIGGER payment_events_no_update
            BEFORE UPDATE ON payment_events
            FOR EACH ROW
            BEGIN
                SELECT RAISE(
                    ABORT,
                    'Payment events cannot be updated'
                );
            END;
        ");

        DB::unprepared("
            CREATE TRIGGER payment_events_no_delete
            BEFORE DELETE ON payment_events
            FOR EACH ROW
            BEGIN
                SELECT RAISE(
                    ABORT,
                    'Payment events cannot be deleted'
                );
            END;
        ");
    }

    public function down(): void
    {
        if (DB::table('payment_events')->exists()) {
            throw new RuntimeException(
                'Cannot roll back while payment event records exist.'
            );
        }

        DB::unprepared(
            'DROP TRIGGER IF EXISTS payment_events_no_delete'
        );

        DB::unprepared(
            'DROP TRIGGER IF EXISTS payment_events_no_update'
        );

        Schema::dropIfExists('payment_events');
    }
};
