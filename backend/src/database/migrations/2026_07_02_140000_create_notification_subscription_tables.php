<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notification_settings', function (Blueprint $table) {
            $table->id()->comment('Идентификатор настроек');
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete()->comment('Пользователь');
            $table->boolean('all_disabled')->default(false)->comment('Отписаться от всех рассылок ЭТП');
            $table->boolean('notify_new_auctions')->default(true)->comment('Оповещать о новых аукционах');
            $table->boolean('notify_new_procedures')->default(true)->comment('Оповещать о новых ТЗП по выбранным категориям');
            $table->boolean('notify_day_before')->default(true)->comment('Напоминание за день до начала');
            $table->boolean('notify_hour_before')->default(true)->comment('Напоминание за час до начала');
            $table->timestamps();
        });

        Schema::create('user_category_subscriptions', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->comment('Пользователь');
            $table->foreignId('classifier_category_id')->constrained()->cascadeOnDelete()->comment('Категория классификатора');
            $table->primary(['user_id', 'classifier_category_id']);
        });

        Schema::create('user_company_group_subscriptions', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->comment('Пользователь');
            $table->foreignId('company_group_id')->constrained()->cascadeOnDelete()->comment('Группа компаний');
            $table->primary(['user_id', 'company_group_id']);
        });

        DB::statement("COMMENT ON TABLE user_notification_settings IS 'Настройки email-оповещений пользователя'");
        DB::statement("COMMENT ON TABLE user_category_subscriptions IS 'Подписки на категории закупок'");
        DB::statement("COMMENT ON TABLE user_company_group_subscriptions IS 'Подписки на группы компаний холдинга'");
    }

    public function down(): void
    {
        Schema::dropIfExists('user_company_group_subscriptions');
        Schema::dropIfExists('user_category_subscriptions');
        Schema::dropIfExists('user_notification_settings');
    }
};
