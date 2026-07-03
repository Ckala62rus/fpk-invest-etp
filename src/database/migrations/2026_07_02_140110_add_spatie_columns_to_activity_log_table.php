<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Добавляет колонки spatie/laravel-activitylog к существующей таблице activity_log.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table): void {
            $table->string('event')->nullable()->after('description')->comment('Тип события: created, updated, deleted');
            $table->uuid('batch_uuid')->nullable()->after('properties')->comment('UUID пакета связанных записей аудита');
        });
    }

    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table): void {
            $table->dropColumn(['event', 'batch_uuid']);
        });
    }
};
