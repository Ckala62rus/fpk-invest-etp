<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_groups', function (Blueprint $table) {
            $table->id()->comment('Идентификатор группы компаний холдинга');
            $table->string('name')->comment('Название группы компаний (1-й уровень классификатора)');
            $table->unsignedInteger('sort_order')->default(0)->comment('Порядок сортировки');
            $table->boolean('is_active')->default(true)->comment('Активна ли группа');
            $table->timestamps();
            $table->softDeletes()->comment('Мягкое удаление');
        });

        Schema::create('classifier_categories', function (Blueprint $table) {
            $table->id()->comment('Идентификатор категории закупки');
            $table->foreignId('company_group_id')->constrained()->cascadeOnDelete()->comment('Группа компаний');
            $table->string('name')->comment('Категория: СМР, ПИР, ИТ и т.д.');
            $table->unsignedInteger('sort_order')->default(0)->comment('Порядок сортировки');
            $table->boolean('is_active')->default(true)->comment('Активна ли категория');
            $table->timestamps();
            $table->softDeletes()->comment('Мягкое удаление');
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id()->comment('Идентификатор предприятия-заказчика');
            $table->foreignId('company_group_id')->constrained()->cascadeOnDelete()->comment('Группа компаний');
            $table->string('name', 500)->comment('Наименование предприятия');
            $table->string('inn', 12)->nullable()->comment('ИНН предприятия');
            $table->boolean('is_external')->default(false)->comment('Внешний заказчик вне холдинга');
            $table->boolean('is_active')->default(true)->comment('Активно ли предприятие');
            $table->timestamps();
            $table->softDeletes()->comment('Мягкое удаление');
        });

        DB::statement("COMMENT ON TABLE company_groups IS 'Группы компаний холдинга ФПК Инвест'");
        DB::statement("COMMENT ON TABLE classifier_categories IS 'Категории предметов закупки (2-й уровень классификатора)'");
        DB::statement("COMMENT ON TABLE companies IS 'Предприятия-заказчики'");
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
        Schema::dropIfExists('classifier_categories');
        Schema::dropIfExists('company_groups');
    }
};
