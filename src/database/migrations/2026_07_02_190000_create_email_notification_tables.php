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
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id()->comment('Идентификатор шаблона');
            $table->string('code', 100)->unique()->comment('Код шаблона: registration_confirm, auction_invite и т.д.');
            $table->string('name')->comment('Название шаблона');
            $table->string('subject', 500)->comment('Тема письма');
            $table->text('body_html')->comment('HTML-тело письма с плейсхолдерами');
            $table->boolean('is_active')->default(true)->comment('Активен ли шаблон');
            $table->timestamps();
        });

        $this->addEnumColumn('notification_templates', 'event_type', 'notification_event_type', nullable: true, comment: 'Тип: по событию или по расписанию');

        Schema::create('email_send_logs', function (Blueprint $table) {
            $table->id()->comment('Идентификатор записи отправки');
            $table->foreignId('template_id')->nullable()->constrained('notification_templates')->nullOnDelete()->comment('Шаблон письма');
            $table->string('recipient_email')->comment('Email получателя');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->comment('Пользователь-получатель');
            $table->string('subject', 500)->comment('Тема отправленного письма');
            $table->text('error')->nullable()->comment('Текст ошибки при неудачной отправке');
            $table->jsonb('payload')->nullable()->comment('Данные для шаблона');
            $table->timestamp('sent_at')->nullable()->comment('Фактическое время отправки');
            $table->timestamp('created_at')->nullable()->comment('Время постановки в очередь');
        });

        $this->addEnumColumn('email_send_logs', 'status', 'email_send_status', default: 'pending', comment: 'Статус отправки письма');

        Schema::create('external_invite_batches', function (Blueprint $table) {
            $table->id()->comment('Идентификатор рассылки');
            $table->foreignId('procedure_id')->constrained()->cascadeOnDelete()->comment('Процедура');
            $table->jsonb('emails')->comment('Список email для приглашения');
            $table->jsonb('duplicates_skipped')->nullable()->comment('Дубликаты, не отправленные');
            $table->foreignId('created_by')->constrained('users')->comment('Кто инициировал рассылку');
            $table->timestamp('sent_at')->nullable()->comment('Дата отправки');
            $table->timestamps();
        });

        DB::statement("COMMENT ON TABLE notification_templates IS 'Шаблоны email-уведомлений'");
        DB::statement("COMMENT ON TABLE email_send_logs IS 'История отправки писем'");
        DB::statement("COMMENT ON TABLE external_invite_batches IS 'Массовые приглашения незарегистрированным email'");
    }

    public function down(): void
    {
        Schema::dropIfExists('external_invite_batches');
        Schema::dropIfExists('email_send_logs');
        Schema::dropIfExists('notification_templates');
    }
};
