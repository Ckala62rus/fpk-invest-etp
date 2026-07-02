<?php

use App\Support\Migration\AddsPostgresEnumColumn;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use AddsPostgresEnumColumn;

    public function up(): void
    {
        Schema::create('procedures', function (Blueprint $table) {
            $table->id()->comment('Идентификатор торгово-закупочной процедуры');
            $table->string('number', 50)->unique()->comment('Номер процедуры');
            $table->string('title', 500)->comment('Название процедуры');
            $table->text('description')->nullable()->comment('Описание');
            $table->foreignId('company_id')->constrained()->comment('Заказчик');
            $table->foreignId('classifier_category_id')->constrained()->comment('Категория закупки');
            $table->foreignId('responsible_user_id')->constrained('users')->comment('Ответственный администратор торгов');
            $table->foreignId('created_by')->constrained('users')->comment('Кто создал процедуру');
            $table->string('customer_contact_name')->nullable()->comment('ФИО заказчика (скрыто от участников)');
            $table->string('customer_contact_email')->nullable()->comment('Email заказчика (скрыто от участников)');
            $table->timestamp('starts_at')->nullable()->comment('Дата и время начала');
            $table->timestamp('ends_at')->nullable()->comment('Дата и время окончания приёма / торгов');
            $table->timestamp('published_at')->nullable()->comment('Дата публикации');
            $table->timestamp('completed_at')->nullable()->comment('Дата завершения');
            $table->boolean('results_published')->default(false)->comment('Итоги опубликованы на странице ТЗП');
            $table->unsignedSmallInteger('storage_years')->default(3)->comment('Срок хранения заявок (лет)');
            $table->foreignId('source_procedure_id')->nullable()->constrained('procedures')->nullOnDelete()->comment('Связь КП → аукцион (2-й этап)');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete()->comment('Кто удалил процедуру');
            $table->timestamps();
            $table->softDeletes()->comment('Мягкое удаление (папка «удалённые»)');
        });

        $this->addEnumColumn('procedures', 'type', 'procedure_type', comment: 'Тип ТЗП: request_for_proposal или auction');
        $this->addEnumColumn('procedures', 'trade_direction', 'trade_direction', nullable: true, comment: 'Направление: purchase — закупка, sale — продажа');
        $this->addEnumColumn('procedures', 'status', 'procedure_status', default: 'draft', comment: 'Статус процедуры');
        $this->addEnumColumn('procedures', 'visibility', 'procedure_visibility', default: 'open', comment: 'Открытость процедуры');

        Schema::table('procedures', function (Blueprint $table) {
            $table->index(['status', 'starts_at']);
            $table->index(['type', 'visibility']);
        });

        Schema::create('auction_settings', function (Blueprint $table) {
            $table->id()->comment('Идентификатор настроек аукциона');
            $table->foreignId('procedure_id')->unique()->constrained()->cascadeOnDelete()->comment('Процедура-аукцион');
            $table->unsignedSmallInteger('extension_minutes')->default(5)->comment('Продление времени при ставке (минуты)');
            $table->unsignedSmallInteger('extension_trigger_minutes')->nullable()->comment('За сколько минут до конца включается продление');
            $table->unsignedSmallInteger('idle_timeout_minutes')->default(30)->comment('Автозавершение при отсутствии ставок (минуты)');
            $table->boolean('forbid_equal_bids')->default(true)->comment('Запрет одинаковых ставок');
            $table->boolean('only_admitted_from_rfp')->default(false)->comment('Только участники, допущенные на 1-м этапе');
            $table->timestamps();
        });

        $this->addEnumColumn('auction_settings', 'bid_mode', 'bid_mode', default: 'standard', comment: 'Режим ставки');
        $this->addEnumColumn('auction_settings', 'auction_mode', 'auction_mode', default: 'decrease', comment: 'Направление аукциона');
        $this->addEnumColumn('auction_settings', 'winner_mode', 'winner_mode', default: 'per_lot', comment: 'Способ определения победителя');

        Schema::create('procedure_lots', function (Blueprint $table) {
            $table->id()->comment('Идентификатор лота');
            $table->foreignId('procedure_id')->constrained()->cascadeOnDelete()->comment('Процедура');
            $table->unsignedInteger('sort_order')->default(0)->comment('Порядок сортировки');
            $table->string('name', 500)->comment('Наименование лота');
            $table->string('unit', 50)->nullable()->comment('Единица измерения');
            $table->decimal('quantity', 15, 3)->nullable()->comment('Потребность');
            $table->decimal('start_price', 15, 2)->comment('Начальная цена');
            $table->decimal('bid_step', 15, 2)->comment('Минимальный шаг ставки');
            $table->decimal('current_price', 15, 2)->nullable()->comment('Текущая лучшая цена');
            $table->foreignId('winner_user_id')->nullable()->constrained('users')->nullOnDelete()->comment('Победитель по лоту');
            $table->timestamps();
        });

        Schema::create('procedure_custom_fields', function (Blueprint $table) {
            $table->id()->comment('Идентификатор настраиваемого поля');
            $table->foreignId('procedure_id')->constrained()->cascadeOnDelete()->comment('Процедура');
            $table->string('label')->comment('Подпись поля');
            $table->jsonb('options')->nullable()->comment('Варианты для select');
            $table->boolean('is_required')->default(false)->comment('Обязательное поле');
            $table->unsignedInteger('sort_order')->default(0)->comment('Порядок отображения');
            $table->timestamps();
        });

        $this->addEnumColumn('procedure_custom_fields', 'scope', 'custom_field_scope', default: 'participant', comment: 'Область применения поля');
        $this->addEnumColumn('procedure_custom_fields', 'field_type', 'custom_field_type', default: 'text', comment: 'Тип данных поля');

        Schema::create('procedure_documents', function (Blueprint $table) {
            $table->id()->comment('Идентификатор документа процедуры');
            $table->foreignId('procedure_id')->constrained()->cascadeOnDelete()->comment('Процедура');
            $table->string('file_path', 500)->comment('Путь к файлу');
            $table->string('file_name')->comment('Имя файла');
            $table->unsignedInteger('version')->default(1)->comment('Версия документации');
            $table->foreignId('uploaded_by')->constrained('users')->comment('Кто загрузил');
            $table->timestamps();
            $table->softDeletes()->comment('Мягкое удаление');
        });

        Schema::create('procedure_participants', function (Blueprint $table) {
            $table->id()->comment('Идентификатор участника процедуры');
            $table->foreignId('procedure_id')->constrained()->cascadeOnDelete()->comment('Процедура');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->comment('Участник');
            $table->timestamp('admitted_at')->nullable()->comment('Дата допуска');
            $table->foreignId('admitted_by')->nullable()->constrained('users')->nullOnDelete()->comment('Кто допустил');
            $table->text('rejection_reason')->nullable()->comment('Причина отклонения');
            $table->timestamps();

            $table->unique(['procedure_id', 'user_id']);
        });

        $this->addEnumColumn('procedure_participants', 'status', 'participant_status', default: 'invited', comment: 'Статус участника в процедуре');

        Schema::create('procedure_change_logs', function (Blueprint $table) {
            $table->id()->comment('Идентификатор записи изменения');
            $table->foreignId('procedure_id')->constrained()->cascadeOnDelete()->comment('Процедура');
            $table->foreignId('changed_by')->constrained('users')->comment('Кто внёс изменение');
            $table->text('change_summary')->comment('Краткое описание изменений');
            $table->jsonb('diff')->nullable()->comment('Детали изменений (diff)');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->comment('Кто согласовал');
            $table->timestamp('approved_at')->nullable()->comment('Дата согласования');
            $table->timestamp('deadline_extended_to')->nullable()->comment('Новый срок после изменений');
            $table->timestamp('notifications_sent_at')->nullable()->comment('Когда отправлены уведомления');
            $table->timestamps();
        });

        $this->addEnumColumn('procedure_change_logs', 'approval_status', 'approval_status', default: 'pending', comment: 'Статус согласования изменений');

        Schema::create('procedure_extra_condition_templates', function (Blueprint $table) {
            $table->id()->comment('Идентификатор шаблона доп. условия');
            $table->string('name')->comment('Название условия (отсрочка, доставка и т.д.)');
            $table->boolean('is_active')->default(true)->comment('Активен ли шаблон');
            $table->timestamps();
        });

        $this->addEnumColumn('procedure_extra_condition_templates', 'field_type', 'custom_field_type', default: 'text', comment: 'Тип значения условия');

        Schema::create('procedure_extra_condition_values', function (Blueprint $table) {
            $table->id()->comment('Идентификатор значения');
            $table->foreignId('procedure_id')->constrained()->cascadeOnDelete()->comment('Процедура');
            $table->foreignId('template_id')->constrained('procedure_extra_condition_templates')->cascadeOnDelete()->comment('Шаблон условия');
            $table->text('value')->nullable()->comment('Значение условия');
            $table->timestamps();

            $table->unique(['procedure_id', 'template_id']);
        });

        DB::statement("COMMENT ON TABLE procedures IS 'Торгово-закупочные процедуры (КП и аукционы)'");
        DB::statement("COMMENT ON TABLE auction_settings IS 'Настройки электронного аукциона'");
        DB::statement("COMMENT ON TABLE procedure_lots IS 'Лоты аукциона'");
        DB::statement("COMMENT ON TABLE procedure_custom_fields IS 'Настраиваемые поля процедуры'");
        DB::statement("COMMENT ON TABLE procedure_documents IS 'Конкурсная документация процедуры'");
        DB::statement("COMMENT ON TABLE procedure_participants IS 'Приглашённые и допущенные участники'");
        DB::statement("COMMENT ON TABLE procedure_change_logs IS 'История изменений документации процедуры'");
        DB::statement("COMMENT ON TABLE procedure_extra_condition_templates IS 'Справочник дополнительных условий аукциона'");
        DB::statement("COMMENT ON TABLE procedure_extra_condition_values IS 'Значения доп. условий для конкретной процедуры'");
    }

    public function down(): void
    {
        Schema::dropIfExists('procedure_extra_condition_values');
        Schema::dropIfExists('procedure_extra_condition_templates');
        Schema::dropIfExists('procedure_change_logs');
        Schema::dropIfExists('procedure_participants');
        Schema::dropIfExists('procedure_documents');
        Schema::dropIfExists('procedure_custom_fields');
        Schema::dropIfExists('procedure_lots');
        Schema::dropIfExists('auction_settings');
        Schema::dropIfExists('procedures');
    }
};
