<?php

namespace Database\Seeders;

use App\Enums\EntityType;
use App\Enums\UserStatus;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Локальные демо-учётки: trade_admin, participant, auditor.
 *
 * Главный администратор создаётся отдельно в SuperAdminSeeder.
 * Seeder идемпотентный: повторный запуск обновляет пароль и роли.
 */
class DemoUsersSeeder extends Seeder
{
    /**
     * Создаёт или обновляет демо-пользователей trade_admin и participant.
     *
     * @return void
     */
    public function run(): void
    {
        $password = (string) env('DEMO_USERS_PASSWORD', 'password');

        $this->seedUser(
            inn: (string) env('TRADE_ADMIN_INN', '770000000001'),
            email: (string) env('TRADE_ADMIN_EMAIL', 'trade_admin@example.com'),
            password: $password,
            role: 'trade_admin',
            profileName: 'ООО «Админ торгов» — демо',
            directorName: 'Администратор торгов',
        );

        $this->seedUser(
            inn: (string) env('PARTICIPANT_INN', '770000000002'),
            email: (string) env('PARTICIPANT_EMAIL', 'participant@example.com'),
            password: $password,
            role: 'participant',
            profileName: 'ООО «Участник Демо»',
            directorName: 'Иванов Иван Иванович',
        );

        $this->seedUser(
            inn: (string) env('AUDITOR_INN', '770000000003'),
            email: (string) env('AUDITOR_EMAIL', 'auditor@example.com'),
            password: $password,
            role: 'auditor',
            profileName: 'Аудитор ЭТП — демо',
            directorName: 'Аудитор Демо',
        );
    }

    /**
     * Создаёт или обновляет одного демо-пользователя с профилем и ролью.
     *
     * @param string $inn ИНН (логин)
     * @param string $email Email
     * @param string $password Пароль в открытом виде
     * @param string $role Slug роли Spatie
     * @param string $profileName Наименование в профиле
     * @param string $directorName ФИО руководителя
     * @return void
     */
    private function seedUser(
        string $inn,
        string $email,
        string $password,
        string $role,
        string $profileName,
        string $directorName,
    ): void {
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

        $user->syncRoles([$role]);

        UserProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'entity_type' => EntityType::Legal,
                'name' => $profileName,
                'phone' => '+70000000000',
                'director_name' => $directorName,
                'contact_persons' => $directorName,
                'pd_consent_at' => now(),
            ],
        );
    }
}
