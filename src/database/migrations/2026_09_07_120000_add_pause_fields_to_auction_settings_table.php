<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Пауза торгов аукциона (фаза 8.2) — без нового значения procedure_status.
 */
return new class extends Migration
{
    /**
     * @return void
     */
    public function up(): void
    {
        Schema::table('auction_settings', function (Blueprint $table) {
            $table->boolean('is_paused')->default(false)->after('only_admitted_from_rfp')
                ->comment('Торги временно приостановлены администратором');
            $table->timestamp('paused_at')->nullable()->after('is_paused')
                ->comment('Когда торги были поставлены на паузу');
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('auction_settings', function (Blueprint $table) {
            $table->dropColumn(['is_paused', 'paused_at']);
        });
    }
};
