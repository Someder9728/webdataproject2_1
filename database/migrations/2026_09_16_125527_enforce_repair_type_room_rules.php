<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            throw new RuntimeException(
                'This migration requires SQLite.'
            );
        }

        $invalidExists = DB::table('repairs')
            ->whereRaw("
                rp_type IS NULL
                OR rp_type NOT IN ('ROOM', 'COMMON')
                OR (rp_type = 'ROOM' AND rooms_r_id IS NULL)
                OR (rp_type = 'COMMON' AND rooms_r_id IS NOT NULL)
            ")
            ->exists();

        if ($invalidExists) {
            throw new RuntimeException(
                'Fix existing repair type and room assignments first.'
            );
        }

        DB::unprepared("
            CREATE TRIGGER repairs_type_room_insert
            BEFORE INSERT ON repairs
            FOR EACH ROW
            WHEN
                NEW.rp_type IS NULL
                OR NEW.rp_type NOT IN ('ROOM', 'COMMON')
                OR (
                    NEW.rp_type = 'ROOM'
                    AND NEW.rooms_r_id IS NULL
                )
                OR (
                    NEW.rp_type = 'COMMON'
                    AND NEW.rooms_r_id IS NOT NULL
                )
            BEGIN
                SELECT RAISE(
                    ABORT,
                    'Invalid repair type and room assignment'
                );
            END;
        ");

        DB::unprepared("
            CREATE TRIGGER repairs_type_room_update
            BEFORE UPDATE ON repairs
            FOR EACH ROW
            WHEN
                NEW.rp_type IS NULL
                OR NEW.rp_type NOT IN ('ROOM', 'COMMON')
                OR (
                    NEW.rp_type = 'ROOM'
                    AND NEW.rooms_r_id IS NULL
                )
                OR (
                    NEW.rp_type = 'COMMON'
                    AND NEW.rooms_r_id IS NOT NULL
                )
            BEGIN
                SELECT RAISE(
                    ABORT,
                    'Invalid repair type and room assignment'
                );
            END;
        ");
    }

    public function down(): void
    {
        DB::unprepared(
            'DROP TRIGGER IF EXISTS repairs_type_room_update'
        );

        DB::unprepared(
            'DROP TRIGGER IF EXISTS repairs_type_room_insert'
        );
    }
};