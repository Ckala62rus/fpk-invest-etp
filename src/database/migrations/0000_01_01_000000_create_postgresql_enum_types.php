<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Нативные PostgreSQL ENUM-типы для ЭТП.
 * Создаются до таблиц, удаляются после отката всех зависимых миграций.
 */
return new class extends Migration
{
    /**
     * Создать ENUM-тип только если его ещё нет.
     * В Postgres migrate:fresh не удаляет типы, поэтому проверяем существование вручную.
     */
    private function createTypeIfNotExists(string $name, array $values): void
    {
        $quotedValues = implode(', ', array_map(
            static fn (string $value): string => "'".$value."'",
            $values,
        ));

        DB::statement(<<<SQL
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = '{$name}') THEN
        CREATE TYPE {$name} AS ENUM ({$quotedValues});
    END IF;
END
$$;
SQL);
    }

    public function up(): void
    {
        $types = [
            'user_status' => ['pending_email', 'pending_approval', 'active', 'blocked'],
            'entity_type' => ['legal', 'individual'],
            'password_reset_admin_status' => ['pending', 'resolved', 'rejected'],
            'procedure_type' => ['request_for_proposal', 'auction'],
            'trade_direction' => ['purchase', 'sale'],
            'procedure_status' => ['draft', 'published', 'accepting', 'review', 'auction_pending', 'in_progress', 'completed', 'cancelled'],
            'procedure_visibility' => ['open', 'closed'],
            'bid_mode' => ['standard', 'step_minimum'],
            'auction_mode' => ['decrease', 'increase'],
            'winner_mode' => ['per_lot', 'total_sum'],
            'custom_field_scope' => ['procedure', 'participant', 'lot'],
            'custom_field_type' => ['text', 'number', 'decimal', 'date', 'boolean', 'select', 'file'],
            'participant_status' => ['invited', 'admitted', 'rejected', 'winner'],
            'approval_status' => ['pending', 'approved', 'rejected'],
            'proposal_status' => ['draft', 'submitted', 'under_review', 'clarification', 'admitted', 'rejected', 'winner'],
            'admission_decision' => ['admit', 'reject'],
            'notification_event_type' => ['event', 'scheduled'],
            'email_send_status' => ['pending', 'sent', 'failed'],
            'complaint_status' => ['new', 'in_progress', 'resolved', 'rejected'],
            'report_format' => ['pdf', 'xlsx', 'doc'],
        ];

        foreach ($types as $name => $values) {
            $this->createTypeIfNotExists($name, $values);
        }
    }

    public function down(): void
    {
        $types = [
            'report_format',
            'complaint_status',
            'email_send_status',
            'notification_event_type',
            'admission_decision',
            'proposal_status',
            'approval_status',
            'participant_status',
            'custom_field_type',
            'custom_field_scope',
            'winner_mode',
            'auction_mode',
            'bid_mode',
            'procedure_visibility',
            'procedure_status',
            'trade_direction',
            'procedure_type',
            'password_reset_admin_status',
            'entity_type',
            'user_status',
        ];

        foreach ($types as $type) {
            DB::statement("DROP TYPE IF EXISTS {$type}");
        }
    }
};
