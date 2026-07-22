<?php

namespace App\Actions\Auth;

use App\DTOs\RegisterUserDTO;
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
     * @param RegisterUserDTO $dto Проверенные данные формы регистрации
     * @return User Созданная учётная запись со связанным профилем
     */
    public function execute(RegisterUserDTO $dto): User
    {
        /** @var User $user */
        $user = DB::transaction(function () use ($dto): User {
            $user = User::query()->create([
                'inn' => $dto->inn,
                'email' => $dto->email,
                'password' => $dto->password,
                'status' => UserStatus::PendingEmail,
            ]);

            UserProfile::query()->create([
                'user_id' => $user->id,
                'entity_type' => $dto->entityType,
                'name' => $dto->name,
                'phone' => $dto->phone,
                'director_name' => $dto->directorName,
                'director_birth_date' => $dto->directorBirthDate,
                'contact_persons' => $dto->contactPersons,
                // pd_consent уже принят в FormRequest; фиксируем момент согласия
                'pd_consent_at' => now(),
            ]);

            foreach ($dto->extraEmails as $email) {
                UserEmail::query()->create([
                    'user_id' => $user->id,
                    'email' => $email,
                ]);
            }

            // Настройки оповещений по умолчанию при регистрации (ТЗ §3.1)
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
