<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id()->comment('Идентификатор роли');
            $table->string('name', 100)->unique()->comment('Системное имя: super_admin, trade_admin, auditor, participant, guest');
            $table->string('display_name')->comment('Отображаемое название роли');
            $table->boolean('is_system')->default(false)->comment('Системная роль (нельзя удалить)');
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id()->comment('Идентификатор права');
            $table->string('name', 100)->unique()->comment('Системное имя права, например procedures.create');
            $table->string('group', 100)->nullable()->comment('Группа для UI');
            $table->timestamps();
        });

        Schema::create('role_permission', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete()->comment('Роль');
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete()->comment('Право');
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('user_role', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->comment('Пользователь');
            $table->foreignId('role_id')->constrained()->cascadeOnDelete()->comment('Роль');
            $table->primary(['user_id', 'role_id']);
        });

        DB::statement("COMMENT ON TABLE roles IS 'Роли пользователей ЭТП'");
        DB::statement("COMMENT ON TABLE permissions IS 'Права доступа'");
        DB::statement("COMMENT ON TABLE role_permission IS 'Связь ролей и прав'");
        DB::statement("COMMENT ON TABLE user_role IS 'Назначенные роли пользователей'");
    }

    public function down(): void
    {
        Schema::dropIfExists('user_role');
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
