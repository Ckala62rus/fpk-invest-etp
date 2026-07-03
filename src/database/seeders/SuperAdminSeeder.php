<?php

namespace Database\Seeders;

use App\Enums\EntityType;
use App\Enums\UserStatus;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Первый главный администратор из переменных окружения (идемпотентный seeder).
 */
class SuperAdminSeeder extends Seeder
{
    /**
     * Создаёт или обновляет учётную запись super_admin и профиль.
     */
    public function run(): void
    {
        $inn = (string) env('SUPER_ADMIN_INN', '770000000000');
        $email = (string) env('SUPER_ADMIN_EMAIL', 'super_admin@example.com');
        $password = (string) env('SUPER_ADMIN_PASSWORD', 'password');

        $user = User::query()->updateOrCreate(
            ['inn' => $inn],
            [
                'email' => $email,
                'password' => Hash::make($password),
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
                'approved_at' => now(),
            ],
        );

        $user->syncRoles(['super_admin']);

        UserProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'entity_type' => EntityType::Legal,
                'name' => 'ООО ФПК «Инвест» — администратор ЭТП',
                'phone' => '+70000000000',
                'director_name' => 'Главный администратор',
                'contact_persons' => 'Главный администратор ЭТП',
                'pd_consent_at' => now(),
            ],
        );
    }
}
