<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Уникальность сессии присутствия: одна запись на пару процедура + пользователь (фаза 8.8).
 */
return new class extends Migration
{
    /**
     * @return void
     */
    public function up(): void
    {
        DB::statement(
            'CREATE UNIQUE INDEX IF NOT EXISTS auction_sessions_procedure_user_unique
             ON auction_sessions (procedure_id, user_id)
             WHERE user_id IS NOT NULL',
        );
    }

    /**
     * @return void
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS auction_sessions_procedure_user_unique');
    }
};
