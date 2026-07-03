<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Приводит RBAC-схему ЭТП к формату spatie/laravel-permission.
 *
 * Сохраняет кастомные поля (display_name, is_system, group) и переносит user_role → model_has_roles.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->string('guard_name', 25)->default('web')->after('name');
        });

        Schema::table('roles', function (Blueprint $table): void {
            $table->dropUnique(['name']);
            $table->unique(['name', 'guard_name']);
        });

        Schema::table('permissions', function (Blueprint $table): void {
            $table->string('guard_name', 25)->default('web')->after('name');
        });

        Schema::table('permissions', function (Blueprint $table): void {
            $table->dropUnique(['name']);
            $table->unique(['name', 'guard_name']);
        });

        Schema::rename('role_permission', 'role_has_permissions');

        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');

            $table->index(['model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');

            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->cascadeOnDelete();

            $table->primary(
                ['role_id', 'model_id', 'model_type'],
                'model_has_roles_role_model_type_primary',
            );
        });

        if (Schema::hasTable('user_role')) {
            foreach (DB::table('user_role')->get() as $row) {
                DB::table('model_has_roles')->insert([
                    'role_id' => $row->role_id,
                    'model_type' => 'App\Models\User',
                    'model_id' => $row->user_id,
                ]);
            }

            Schema::drop('user_role');
        }

        Schema::create('model_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');

            $table->index(['model_id', 'model_type'], 'model_has_permissions_model_id_model_type_index');

            $table->foreign('permission_id')
                ->references('id')
                ->on('permissions')
                ->cascadeOnDelete();

            $table->primary(
                ['permission_id', 'model_id', 'model_type'],
                'model_has_permissions_permission_model_type_primary',
            );
        });

        DB::statement("COMMENT ON TABLE model_has_roles IS 'Назначенные роли пользователей (Spatie Permission)'");
        DB::statement("COMMENT ON TABLE model_has_permissions IS 'Прямые права пользователей (Spatie Permission)'");
        DB::statement("COMMENT ON TABLE role_has_permissions IS 'Связь ролей и прав'");
    }

    public function down(): void
    {
        Schema::dropIfExists('model_has_permissions');

        Schema::create('user_role', function (Blueprint $table): void {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'role_id']);
        });

        if (Schema::hasTable('model_has_roles')) {
            foreach (
                DB::table('model_has_roles')
                    ->where('model_type', 'App\Models\User')
                    ->get() as $row
            ) {
                DB::table('user_role')->insert([
                    'user_id' => $row->model_id,
                    'role_id' => $row->role_id,
                ]);
            }

            Schema::drop('model_has_roles');
        }

        Schema::rename('role_has_permissions', 'role_permission');

        Schema::table('permissions', function (Blueprint $table): void {
            $table->dropUnique(['name', 'guard_name']);
            $table->dropColumn('guard_name');
            $table->unique('name');
        });

        Schema::table('roles', function (Blueprint $table): void {
            $table->dropUnique(['name', 'guard_name']);
            $table->dropColumn('guard_name');
            $table->unique('name');
        });
    }
};
