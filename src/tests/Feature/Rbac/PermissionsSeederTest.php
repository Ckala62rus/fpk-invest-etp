<?php

namespace Tests\Feature\Rbac;

use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Проверяет seeder ролей и детальных permissions фазы 2.1.
 */
class PermissionsSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Запускает RolesAndPermissionsSeeder перед каждым тестом.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /**
     * Проверяет, что ключевые права созданы с группой для UI.
     *
     * @return void
     */
    public function test_permissions_are_created_with_groups(): void
    {
        $this->assertDatabaseHas('permissions', [
            'name' => 'users.assign_roles',
            'group' => 'users',
            'guard_name' => 'web',
        ]);

        $this->assertDatabaseHas('permissions', [
            'name' => 'activity_log.view',
            'group' => 'audit',
        ]);

        $this->assertGreaterThanOrEqual(20, Permission::query()->count());
    }

    /**
     * Проверяет привязку прав: super_admin — всё, trade_admin — без assign_roles.
     *
     * @return void
     */
    public function test_role_permission_bindings(): void
    {
        $superAdmin = Role::findByName('super_admin', 'web');
        $tradeAdmin = Role::findByName('trade_admin', 'web');
        $auditor = Role::findByName('auditor', 'web');
        $participant = Role::findByName('participant', 'web');

        $this->assertTrue($superAdmin->hasPermissionTo('users.assign_roles'));
        $this->assertTrue($superAdmin->hasPermissionTo('settings.update'));

        $this->assertTrue($tradeAdmin->hasPermissionTo('users.block'));
        $this->assertTrue($tradeAdmin->hasPermissionTo('users.approve'));
        $this->assertFalse($tradeAdmin->hasPermissionTo('users.assign_roles'));

        $this->assertTrue($auditor->hasPermissionTo('activity_log.view'));
        $this->assertTrue($auditor->hasPermissionTo('activity_log.export'));
        $this->assertFalse($auditor->hasPermissionTo('users.block'));

        // users.view — админский список, не право участника на свой профиль
        $this->assertFalse($participant->hasPermissionTo('users.view'));
        $this->assertTrue($participant->hasPermissionTo('bids.create'));
    }

    /**
     * Повторный seed не ломает данные и оставляет те же привязки.
     *
     * @return void
     */
    public function test_seeder_is_idempotent(): void
    {
        $countBefore = Permission::query()->count();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertSame($countBefore, Permission::query()->count());
        $this->assertTrue(Role::findByName('super_admin', 'web')->hasPermissionTo('users.delete'));
    }
}
