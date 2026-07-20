<?php

namespace App\Actions\Auth;

use App\Enums\UserStatus;
use App\Mail\VerifyEmailMail;
use App\Models\User;
use App\Models\UserEmail;
use App\Models\UserNotificationSetting;
use App\Models\UserProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Действие регистрации участника ЭТП (электронной торговой площадки).
 *
 * Создаёт связанные сущности регистрации в одной транзакции и отправляет письмо подтверждения email.
 */
class RegisterUserAction
{
    /**
     * Регистрирует пользователя и отправляет ссылку подтверждения email.
     *
     * @param array<string, mixed> $data Проверенные данные формы регистрации
     * @return User Созданная учётная запись со связанным профилем
     */
    public function execute(array $data): User
    {
        /** @var User $user */
        $user = DB::transaction(function () use ($data): User {
            $user = User::query()->create([
                'inn' => $data['inn'],
                'email' => $data['email'],
                'password' => $data['password'],
                'status' => UserStatus::PendingEmail,
            ]);

            UserProfile::query()->create([
                'user_id' => $user->id,
                'entity_type' => $data['entity_type'],
                'name' => $data['name'],
                'phone' => $data['phone'],
                'director_name' => $data['director_name'],
                'director_birth_date' => $data['director_birth_date'] ?? null,
                'contact_persons' => $data['contact_persons'],
                'pd_consent_at' => now(),
            ]);

            foreach ($data['extra_emails'] ?? [] as $email) {
                UserEmail::query()->create(['user_id' => $user->id, 'email' => $email]);
            }

            UserNotificationSetting::query()->create([
                'user_id' => $user->id,
                'all_disabled' => false,
                'notify_new_auctions' => true,
                'notify_new_procedures' => true,
                'notify_day_before' => true,
                'notify_hour_before' => true,
            ]);

            $user->assignRole('participant');

            return $user;
        });

        Mail::to($user->email)->send(new VerifyEmailMail($user));

        return $user->load('profile');
    }
}
