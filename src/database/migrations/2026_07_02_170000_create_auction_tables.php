<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auction_bids', function (Blueprint $table) {
            $table->id()->comment('Идентификатор ставки');
            $table->foreignId('procedure_id')->constrained()->cascadeOnDelete()->comment('Процедура-аукцион');
            $table->foreignId('lot_id')->constrained('procedure_lots')->cascadeOnDelete()->comment('Лот');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->comment('Участник');
            $table->decimal('amount', 15, 2)->comment('Сумма ставки');
            $table->boolean('is_cancelled')->default(false)->comment('Ставка отменена администратором');
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete()->comment('Кто отменил ставку');
            $table->timestamp('cancelled_at')->nullable()->comment('Дата отмены ставки');
            $table->text('cancel_reason')->nullable()->comment('Причина отмены');
            $table->ipAddress('ip_address')->nullable()->comment('IP-адрес при подаче ставки');
            $table->timestamp('created_at')->nullable()->comment('Дата и время ставки');

            $table->index(['lot_id', 'created_at']);
            $table->index(['procedure_id', 'user_id']);
        });

        Schema::create('auction_sessions', function (Blueprint $table) {
            $table->id()->comment('Идентификатор сессии посещения');
            $table->foreignId('procedure_id')->constrained()->cascadeOnDelete()->comment('Процедура-аукцион');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->comment('Участник (null для гостя)');
            $table->timestamp('first_seen_at')->comment('Первый визит');
            $table->timestamp('last_seen_at')->comment('Последняя активность');
            $table->boolean('is_online')->default(false)->comment('Сейчас на странице аукциона');
        });

        Schema::create('auction_protocols', function (Blueprint $table) {
            $table->id()->comment('Идентификатор протокола');
            $table->foreignId('procedure_id')->constrained()->cascadeOnDelete()->comment('Процедура-аукцион');
            $table->string('file_path', 500)->comment('Путь к PDF-протоколу');
            $table->foreignId('generated_by')->constrained('users')->comment('Кто сформировал');
            $table->timestamp('generated_at')->comment('Дата формирования');
            $table->unsignedInteger('template_version')->default(1)->comment('Версия шаблона протокола');
        });

        DB::statement("COMMENT ON TABLE auction_bids IS 'Ставки участников на аукционе'");
        DB::statement("COMMENT ON TABLE auction_sessions IS 'Мониторинг посещений страницы аукциона'");
        DB::statement("COMMENT ON TABLE auction_protocols IS 'Сгенерированные PDF-протоколы аукционов'");
    }

    public function down(): void
    {
        Schema::dropIfExists('auction_protocols');
        Schema::dropIfExists('auction_sessions');
        Schema::dropIfExists('auction_bids');
    }
};
