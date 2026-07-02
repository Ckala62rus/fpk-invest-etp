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
        Schema::create('proposals', function (Blueprint $table) {
            $table->id()->comment('Идентификатор заявки (КП)');
            $table->foreignId('procedure_id')->constrained()->cascadeOnDelete()->comment('Процедура');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->comment('Участник');
            $table->timestamp('submitted_at')->nullable()->comment('Дата подачи заявки');
            $table->timestamp('contract_form_agreed_at')->nullable()->comment('Согласие с формой договора');
            $table->unsignedInteger('version')->default(1)->comment('Версия заявки после уточнения');
            $table->foreignId('parent_proposal_id')->nullable()->constrained('proposals')->nullOnDelete()->comment('Предыдущая версия заявки');
            $table->timestamps();
        });

        $this->addEnumColumn('proposals', 'status', 'proposal_status', default: 'draft', comment: 'Статус заявки участника');

        Schema::create('proposal_field_values', function (Blueprint $table) {
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete()->comment('Заявка');
            $table->foreignId('procedure_custom_field_id')->constrained()->cascadeOnDelete()->comment('Настраиваемое поле');
            $table->text('value')->nullable()->comment('Значение поля');
            $table->primary(['proposal_id', 'procedure_custom_field_id']);
        });

        Schema::create('proposal_documents', function (Blueprint $table) {
            $table->id()->comment('Идентификатор документа заявки');
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete()->comment('Заявка');
            $table->string('file_path', 500)->comment('Путь к файлу');
            $table->string('file_name')->comment('Имя файла');
            $table->string('type', 100)->nullable()->comment('Тип документа');
            $table->timestamp('created_at')->nullable()->comment('Дата загрузки');
        });

        Schema::create('admission_decisions', function (Blueprint $table) {
            $table->id()->comment('Идентификатор решения о допуске');
            $table->foreignId('proposal_id')->unique()->constrained()->cascadeOnDelete()->comment('Заявка');
            $table->text('reason')->nullable()->comment('Причина допуска/недопуска');
            $table->foreignId('decided_by')->constrained('users')->comment('Кто принял решение');
            $table->timestamp('decided_at')->comment('Дата решения');
            $table->timestamp('clarification_deadline')->nullable()->comment('Срок для уточнения КП');
            $table->timestamps();
        });

        $this->addEnumColumn('admission_decisions', 'decision', 'admission_decision', comment: 'Решение: admit или reject');

        Schema::create('proposal_messages', function (Blueprint $table) {
            $table->id()->comment('Идентификатор сообщения');
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete()->comment('Заявка');
            $table->foreignId('sender_id')->constrained('users')->comment('Отправитель');
            $table->text('message')->comment('Текст сообщения');
            $table->jsonb('attachments')->nullable()->comment('Вложения');
            $table->timestamp('created_at')->nullable()->comment('Дата отправки');
        });

        Schema::create('proposal_access_logs', function (Blueprint $table) {
            $table->id()->comment('Идентификатор записи доступа');
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete()->comment('Заявка');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->comment('Пользователь');
            $table->string('action', 100)->comment('Действие: view, download и т.д.');
            $table->string('ip_address', 45)->nullable()->comment('IP-адрес');
            $table->timestamp('created_at')->nullable()->comment('Дата действия');
        });

        DB::statement("COMMENT ON TABLE proposals IS 'Заявки участников на запрос предложений'");
        DB::statement("COMMENT ON TABLE proposal_field_values IS 'Значения настраиваемых полей заявки'");
        DB::statement("COMMENT ON TABLE proposal_documents IS 'Документы, прикреплённые к заявке'");
        DB::statement("COMMENT ON TABLE admission_decisions IS 'Решения о допуске/недопуске участника'");
        DB::statement("COMMENT ON TABLE proposal_messages IS 'Переписка по уточнению заявки'");
        DB::statement("COMMENT ON TABLE proposal_access_logs IS 'Лог обращений к заявкам'");
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_access_logs');
        Schema::dropIfExists('proposal_messages');
        Schema::dropIfExists('admission_decisions');
        Schema::dropIfExists('proposal_documents');
        Schema::dropIfExists('proposal_field_values');
        Schema::dropIfExists('proposals');
    }
};
