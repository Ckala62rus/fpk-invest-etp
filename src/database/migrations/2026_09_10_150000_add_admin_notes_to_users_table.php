<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Служебный комментарий администратора о пользователе (заметки в карточке).
 */
return new class extends Migration
{
    /**
     * @return void
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->text('admin_notes')
                ->nullable()
                ->after('block_reason')
                ->comment('Служебные заметки админа / администратора торгов о пользователе');
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('admin_notes');
        });
    }
};
