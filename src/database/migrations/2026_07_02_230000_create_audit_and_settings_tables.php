<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id()->comment('Идентификатор записи аудита');
            $table->string('log_name', 100)->nullable()->comment('Канал лога');
            $table->text('description')->comment('Описание действия');
            $table->nullableMorphs('subject', 'activity_log_subject_index');
            $table->nullableMorphs('causer', 'activity_log_causer_index');
            $table->jsonb('properties')->nullable()->comment('Дополнительные данные действия');
            $table->timestamp('created_at')->nullable()->comment('Дата и время действия');
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary()->comment('Ключ настройки');
            $table->jsonb('value')->comment('Значение настройки (JSON)');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete()->comment('Кто последний изменил');
            $table->timestamp('updated_at')->nullable()->comment('Дата изменения');
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id()->comment('Идентификатор токена');
            $table->morphs('tokenable');
            $table->text('name')->comment('Название токена');
            $table->string('token', 64)->unique()->comment('Хеш токена API');
            $table->text('abilities')->nullable()->comment('Права токена');
            $table->timestamp('last_used_at')->nullable()->comment('Последнее использование');
            $table->timestamp('expires_at')->nullable()->comment('Срок действия');
            $table->timestamps();
        });

        DB::statement("COMMENT ON TABLE activity_log IS 'Аудит действий пользователей на площадке'");
        DB::statement("COMMENT ON TABLE settings IS 'Глобальные настройки ЭТП'");
        DB::statement("COMMENT ON TABLE personal_access_tokens IS 'API-токены Sanctum'");
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('activity_log');
    }
};
