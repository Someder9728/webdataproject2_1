<?php

use App\Support\AuditHistorySchema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            throw new RuntimeException('This migration requires SQLite.');
        }
        DB::transaction(function () {
            $payments = DB::table('payment_events')->orderBy('pe_id')->get();
            $repairs = DB::table('repair_histories')->orderBy('rph_id')->get();
            $floor = max((int) $payments->max('pe_id'), (int) $repairs->max('rph_id'), (int) DB::table('audit_events')->max('ae_id'));
            DB::table('sqlite_sequence')->updateOrInsert(['name' => 'audit_events'], ['seq' => $floor]);
            DB::unprepared('DROP TRIGGER IF EXISTS audit_events_prevent_update');
            foreach (['payment' => $payments, 'repair' => $repairs] as $kind => $rows) {
                foreach ($rows as $row) {
                    $data = (array) $row;
                    $id = $kind === 'payment' ? $row->payments_p_id : $row->repairs_rp_id;
                    $actor = $kind === 'payment' ? $row->actor_user_id : $row->changed_by_user_id;
                    $action = $kind === 'payment' ? match ($row->event_type) {
                        'PROOF_SUBMITTED' => 'payment_proof_submitted', 'PAYMENT_APPROVED' => 'payment_approved',
                        'PAYMENT_REJECTED' => 'payment_rejected', 'WALK_IN_RECORDED' => 'walk_in_payment_recorded', default => 'payment_history_recorded',
                    } : ($row->rph_status === 'REPORTED' ? 'repair_created' : 'repair_status_changed');
                    $type = $kind === 'payment' ? 'payments' : 'repairs';
                    $data['kind'] = $kind;
                    $data['legacy_id'] = $kind === 'payment' ? $row->pe_id : $row->rph_id;
                    // Identity lives in Audit columns, not duplicated in the JSON snapshot.
                    unset($data['pe_id'], $data['rph_id'], $data['payments_p_id'], $data['repairs_rp_id'], $data['actor_user_id'], $data['changed_by_user_id'], $data['created_at']);
                    $candidates = DB::table('audit_events')->where('entity_type', $type)->where('entity_id', $id)
                        ->where('actor_user_id', $actor)->where('action', $action)->where('created_at', $row->created_at)->get()
                        ->filter(fn ($a) => ! isset(json_decode($a->new_values ?? '{}', true)['history']));
                    if ($candidates->count() === 1) {
                        $audit = $candidates->first();
                        $values = json_decode($audit->new_values ?? '{}', true, 512, JSON_THROW_ON_ERROR);
                        $values['history'] = $data;
                        DB::table('audit_events')->where('ae_id', $audit->ae_id)->update(['new_values' => json_encode($values, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)]);
                    } else {
                        DB::table('audit_events')->insert(['actor_user_id' => $actor, 'entity_type' => $type, 'entity_id' => $id,
                            'action' => $action, 'old_values' => null,
                            'new_values' => json_encode(['history' => $data], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                            'reason' => $data['reason'] ?? null, 'created_at' => $row->created_at]);
                    }
                }
            }
            // Verify every legacy ID and payload before removing either storage table.
            foreach (['payment' => $payments, 'repair' => $repairs] as $kind => $rows) {
                foreach ($rows as $row) {
                    $key = $kind === 'payment' ? $row->pe_id : $row->rph_id;
                    $matches = DB::table('audit_events')->whereRaw("json_extract(new_values, '$.history.kind') = ?", [$kind])
                        ->whereRaw("json_extract(new_values, '$.history.legacy_id') = ?", [$key])->count();
                    if ($matches !== 1) {
                        throw new RuntimeException('History verification failed before table removal.');
                    }
                }
            }
            DB::unprepared("CREATE TRIGGER audit_events_prevent_update BEFORE UPDATE ON audit_events BEGIN SELECT RAISE(ABORT, 'Audit events cannot be updated'); END");
            Schema::drop('payment_events');
            Schema::drop('repair_histories');
            DB::statement('CREATE INDEX audit_events_history_lookup ON audit_events (entity_type, entity_id, created_at, ae_id)');
            AuditHistorySchema::createViews();
            if (DB::table('payment_events')->count() !== $payments->count() || DB::table('repair_histories')->count() !== $repairs->count()) {
                throw new RuntimeException('History view count differs from original storage.');
            }
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Centralized history is not automatically reversible. Restore the verified pre-migration backup together with the previous code.');
    }
};
