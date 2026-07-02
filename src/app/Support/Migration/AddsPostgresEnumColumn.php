<?php

namespace App\Support\Migration;

use Illuminate\Support\Facades\DB;

trait AddsPostgresEnumColumn
{
    /**
     * Добавляет колонку с нативным PostgreSQL ENUM после создания таблицы через Schema.
     */
    protected function addEnumColumn(
        string $table,
        string $column,
        string $enumType,
        ?string $default = null,
        bool $nullable = false,
        ?string $comment = null,
    ): void {
        $nullableSql = $nullable ? '' : ' NOT NULL';
        $defaultSql = $default !== null ? " DEFAULT '{$default}'" : '';
        $nullSql = $nullable ? ' NULL' : '';

        DB::statement("ALTER TABLE {$table} ADD COLUMN {$column} {$enumType}{$nullSql}{$nullableSql}{$defaultSql}");

        if ($comment !== null) {
            DB::statement('COMMENT ON COLUMN '.$table.'.'.$column.' IS '.DB::getPdo()->quote($comment));
        }
    }
}
