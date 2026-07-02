<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_survey_templates', function (Blueprint $table) {
            $table->id()->comment('Идентификатор вопроса опроса');
            $table->string('question')->comment('Текст вопроса');
            $table->string('field_type', 50)->comment('Тип ответа: boolean, rating, text и т.д.');
            $table->jsonb('options')->nullable()->comment('Варианты ответа');
            $table->boolean('is_required')->default(false)->comment('Обязательный вопрос');
            $table->unsignedInteger('sort_order')->default(0)->comment('Порядок вопроса');
            $table->jsonb('conditional_logic')->nullable()->comment('Условная логика показа вопроса');
            $table->timestamps();
        });

        Schema::create('evaluation_surveys', function (Blueprint $table) {
            $table->id()->comment('Идентификатор опроса');
            $table->foreignId('procedure_id')->constrained()->cascadeOnDelete()->comment('Завершённая процедура');
            $table->string('token')->unique()->comment('Токен ссылки на опрос');
            $table->timestamp('sent_at')->nullable()->comment('Дата отправки опроса');
            $table->timestamp('completed_at')->nullable()->comment('Дата заполнения опроса');
            $table->unsignedSmallInteger('reminder_stage')->default(0)->comment('Этап напоминаний (1 нед, 3 дня, …)');
            $table->timestamps();
        });

        Schema::create('evaluation_responses', function (Blueprint $table) {
            $table->id()->comment('Идентификатор ответа');
            $table->foreignId('survey_id')->constrained('evaluation_surveys')->cascadeOnDelete()->comment('Опрос');
            $table->foreignId('question_id')->constrained('evaluation_survey_templates')->cascadeOnDelete()->comment('Вопрос');
            $table->jsonb('answer')->comment('Ответ заказчика');
            $table->timestamp('created_at')->nullable()->comment('Дата ответа');
        });

        Schema::create('participant_ratings', function (Blueprint $table) {
            $table->id()->comment('Идентификатор оценки');
            $table->foreignId('winner_user_id')->constrained('users')->cascadeOnDelete()->comment('Победитель (участник)');
            $table->foreignId('procedure_id')->constrained()->cascadeOnDelete()->comment('Процедура');
            $table->unsignedTinyInteger('contractor_score')->nullable()->comment('Оценка подрядчика (1–5)');
            $table->unsignedTinyInteger('product_score')->nullable()->comment('Оценка продукции (1–5)');
            $table->text('comment')->nullable()->comment('Комментарий заказчика');
            $table->timestamp('created_at')->nullable()->comment('Дата оценки');
        });

        DB::statement("COMMENT ON TABLE evaluation_survey_templates IS 'Шаблоны вопросов опроса качества закупки'");
        DB::statement("COMMENT ON TABLE evaluation_surveys IS 'Опросы заказчиков после завершения процедуры'");
        DB::statement("COMMENT ON TABLE evaluation_responses IS 'Ответы на вопросы опроса'");
        DB::statement("COMMENT ON TABLE participant_ratings IS 'Оценки победителей в профиле участника'");
    }

    public function down(): void
    {
        Schema::dropIfExists('participant_ratings');
        Schema::dropIfExists('evaluation_responses');
        Schema::dropIfExists('evaluation_surveys');
        Schema::dropIfExists('evaluation_survey_templates');
    }
};
