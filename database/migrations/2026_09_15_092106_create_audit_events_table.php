<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_events', function (Blueprint $table) {
            $table->id('ae_id');

            $table->foreignId('actor_user_id')
                ->nullable()
                ->constrained('users', 'u_id')
                ->restrictOnDelete();

            $table->string('entity_type', 100);
            $table->unsignedBigInteger('entity_id');
            $table->string('action', 100);

            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('reason')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(
                ['entity_type', 'entity_id', 'created_at'],
                'audit_events_entity_time_index'
            );
        });

        // Audit เป็น append-only: เพิ่มได้ แต่แก้และลบไม่ได้
        DB::unprepared("
            CREATE TRIGGER audit_events_prevent_update
            BEFORE UPDATE ON audit_events
            BEGIN
                SELECT RAISE(ABORT, 'Audit events cannot be updated');
            END;
        ");

        DB::unprepared("
            CREATE TRIGGER audit_events_prevent_delete
            BEFORE DELETE ON audit_events
            BEGIN
                SELECT RAISE(ABORT, 'Audit events cannot be deleted');
            END;
        ");
    }

    public function down(): void
    {
        DB::unprepared(
            'DROP TRIGGER IF EXISTS audit_events_prevent_delete'
        );

        DB::unprepared(
            'DROP TRIGGER IF EXISTS audit_events_prevent_update'
        );

        Schema::dropIfExists('audit_events');
    }
};