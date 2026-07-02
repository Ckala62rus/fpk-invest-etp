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
        Schema::create('users', function (Blueprint $table) {
            $table->id()->comment('Идентификатор пользователя');
            $table->string('inn', 12)->unique()->comment('ИНН, используется как логин при входе');
            $table->string('email')->unique()->comment('Основной email');
            $table->timestamp('email_verified_at')->nullable()->comment('Дата подтверждения email при регистрации');
            $table->string('password')->comment('Хеш пароля');
            $table->timestamp('blocked_until')->nullable()->comment('Дата окончания блокировки');
            $table->text('block_reason')->nullable()->comment('Причина блокировки');
            $table->unsignedSmallInteger('failed_login_attempts')->default(0)->comment('Счётчик неудачных входов (reCAPTCHA после 5)');
            $table->timestamp('approved_at')->nullable()->comment('Дата активации администратором');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->comment('Кто одобрил регистрацию');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes()->comment('Мягкое удаление учётной записи');
        });

        $this->addEnumColumn(
            table: 'users',
            column: 'status',
            enumType: 'user_status',
            default: 'pending_email',
            comment: 'Статус учётной записи: pending_email, pending_approval, active, blocked',
        );

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index()->comment('Пользователь сессии');
            $table->string('ip_address', 45)->nullable()->comment('IP-адрес');
            $table->text('user_agent')->nullable()->comment('User-Agent браузера');
            $table->longText('payload')->comment('Данные сессии');
            $table->integer('last_activity')->index()->comment('Время последней активности (unix)');
        });

        DB::statement("COMMENT ON TABLE users IS 'Пользователи ЭТП'");
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
    }
};
