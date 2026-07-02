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
        Schema::create('report_templates', function (Blueprint $table) {
            $table->id()->comment('Идентификатор шаблона отчёта');
            $table->string('name')->comment('Название шаблона');
            $table->jsonb('query_config')->comment('Конфигурация выборки данных');
            $table->jsonb('columns')->comment('Колонки отчёта');
            $table->foreignId('created_by')->constrained('users')->comment('Кто создал шаблон');
            $table->timestamps();
        });

        Schema::create('report_runs', function (Blueprint $table) {
            $table->id()->comment('Идентификатор запуска отчёта');
            $table->foreignId('template_id')->constrained('report_templates')->cascadeOnDelete()->comment('Шаблон отчёта');
            $table->jsonb('filters')->nullable()->comment('Применённые фильтры');
            $table->string('file_path', 500)->nullable()->comment('Путь к сгенерированному файлу');
            $table->foreignId('generated_by')->constrained('users')->comment('Кто сформировал отчёт');
            $table->timestamp('generated_at')->comment('Дата формирования');
        });

        $this->addEnumColumn('report_runs', 'format', 'report_format', default: 'pdf', comment: 'Формат файла отчёта');

        DB::statement("COMMENT ON TABLE report_templates IS 'Шаблоны отчётов'");
        DB::statement("COMMENT ON TABLE report_runs IS 'История сформированных отчётов'");
    }

    public function down(): void
    {
        Schema::dropIfExists('report_runs');
        Schema::dropIfExists('report_templates');
    }
};
