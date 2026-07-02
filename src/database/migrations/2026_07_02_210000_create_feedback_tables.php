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
        Schema::create('complaints', function (Blueprint $table) {
            $table->id()->comment('Идентификатор жалобы');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->comment('Пользователь (если авторизован)');
            $table->string('name')->nullable()->comment('Имя заявителя');
            $table->string('email')->nullable()->comment('Email заявителя');
            $table->string('subject')->comment('Тема жалобы');
            $table->text('message')->comment('Текст жалобы');
            $table->timestamp('created_at')->nullable()->comment('Дата подачи');
        });

        $this->addEnumColumn('complaints', 'status', 'complaint_status', default: 'new', comment: 'Статус обработки жалобы');

        Schema::create('corruption_reports', function (Blueprint $table) {
            $table->id()->comment('Идентификатор сообщения');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->comment('Пользователь (если авторизован)');
            $table->string('name')->nullable()->comment('Имя заявителя');
            $table->string('email')->nullable()->comment('Email заявителя');
            $table->text('message')->comment('Текст сообщения о коррупции');
            $table->timestamp('created_at')->nullable()->comment('Дата подачи');
        });

        DB::statement("COMMENT ON TABLE complaints IS 'Жалобы (кнопка «Подать жалобу»)'");
        DB::statement("COMMENT ON TABLE corruption_reports IS 'Сообщения о коррупционной составляющей'");
    }

    public function down(): void
    {
        Schema::dropIfExists('corruption_reports');
        Schema::dropIfExists('complaints');
    }
};
