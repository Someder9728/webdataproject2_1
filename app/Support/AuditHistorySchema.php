<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class AuditHistorySchema
{
    public static function createViews(): void
    {
        $payment = [
            'event_type', 'from_status', 'to_status', 'amount', 'payment_date', 'method', 'proof_path', 'note',
        ];
        $fields = [];
        foreach ($payment as $field) {
            $fields[] = "json_extract(new_values, '$.history.{$field}') AS {$field}";
        }
        DB::statement("CREATE VIEW payment_events AS SELECT
            CAST(coalesce(json_extract(new_values, '$.history.legacy_id'), ae_id) AS INTEGER) AS pe_id,
            entity_id AS payments_p_id, actor_user_id, ".implode(', ', $fields).",
            coalesce(json_extract(new_values, '$.history.reason'), reason) AS reason, created_at FROM audit_events
            WHERE entity_type = 'payments' AND CASE WHEN json_valid(new_values) THEN json_extract(new_values, '$.history.kind') END = 'payment'");
        $fields = [];
        foreach (['rph_name', 'rph_description', 'rph_status', 'rph_type', 'updated_at', 'deleted_at'] as $field) {
            $fields[] = "json_extract(new_values, '$.history.{$field}') AS {$field}";
        }
        DB::statement("CREATE VIEW repair_histories AS SELECT
            CAST(coalesce(json_extract(new_values, '$.history.legacy_id'), ae_id) AS INTEGER) AS rph_id,
            entity_id AS repairs_rp_id, actor_user_id AS changed_by_user_id, created_at,
            ".implode(', ', $fields)." FROM audit_events
            WHERE entity_type = 'repairs' AND CASE WHEN json_valid(new_values) THEN json_extract(new_values, '$.history.kind') END = 'repair'");
        foreach (['payment_events' => 'Payment events', 'repair_histories' => 'Repair histories'] as $view => $label) {
            foreach (['UPDATE' => 'updated', 'DELETE' => 'deleted'] as $verb => $past) {
                DB::unprepared("CREATE TRIGGER {$view}_immutable_{$verb} INSTEAD OF {$verb} ON {$view}
                    BEGIN SELECT RAISE(ABORT, '{$label} cannot be {$past}'); END");
            }
        }
        // Legacy direct inserts are rejected. Application adapters write one Audit row.
        DB::unprepared("CREATE TRIGGER repair_histories_insert_guard INSTEAD OF INSERT ON repair_histories BEGIN
            SELECT CASE WHEN NEW.changed_by_user_id IS NULL THEN RAISE(ABORT, 'NOT NULL constraint failed: actor_user_id')
            ELSE RAISE(ABORT, 'Write history through audit_events') END; END");
        DB::unprepared("CREATE TRIGGER payment_events_insert_guard INSTEAD OF INSERT ON payment_events BEGIN
            SELECT RAISE(ABORT, 'Write history through audit_events'); END");
        DB::unprepared("CREATE TRIGGER audit_history_validate BEFORE INSERT ON audit_events
            WHEN json_valid(NEW.new_values) AND json_extract(NEW.new_values, '$.history.kind') IN ('payment','repair')
            BEGIN
                SELECT CASE WHEN NEW.actor_user_id IS NULL THEN RAISE(ABORT, 'NOT NULL constraint failed: actor_user_id') END;
                SELECT CASE WHEN json_extract(NEW.new_values, '$.history.kind') = 'payment'
                    AND (NEW.entity_type <> 'payments' OR NOT EXISTS(SELECT 1 FROM payments WHERE p_id = NEW.entity_id))
                    THEN RAISE(ABORT, 'FOREIGN KEY constraint failed: payment history') END;
                SELECT CASE WHEN json_extract(NEW.new_values, '$.history.kind') = 'repair'
                    AND (NEW.entity_type <> 'repairs' OR NOT EXISTS(SELECT 1 FROM repairs WHERE rp_id = NEW.entity_id))
                    THEN RAISE(ABORT, 'FOREIGN KEY constraint failed: repair history') END;
                SELECT CASE WHEN json_extract(NEW.new_values, '$.history.kind') = 'payment'
                    AND (json_extract(NEW.new_values, '$.history.event_type') IS NULL
                    OR json_extract(NEW.new_values, '$.history.to_status') IS NULL
                    OR json_extract(NEW.new_values, '$.history.amount') IS NULL)
                    THEN RAISE(ABORT, 'NOT NULL constraint failed: payment snapshot') END;
            END");
        foreach (['payments' => ['p_id', 'payment'], 'repairs' => ['rp_id', 'repair']] as $table => [$key,$kind]) {
            DB::unprepared("CREATE TRIGGER {$table}_audit_history_restrict BEFORE DELETE ON {$table}
                WHEN EXISTS(SELECT 1 FROM audit_events WHERE entity_type = '{$table}' AND entity_id = OLD.{$key}
                AND CASE WHEN json_valid(new_values) THEN json_extract(new_values, '$.history.kind') END = '{$kind}')
                BEGIN SELECT RAISE(ABORT, 'FOREIGN KEY constraint failed: immutable history'); END");
        }
    }
}
