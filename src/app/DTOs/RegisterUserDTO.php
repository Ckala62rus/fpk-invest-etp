<?php

namespace App\DTOs;

use App\Enums\EntityType;

/**
 * DTO (Data Transfer Object) данных регистрации участника ЭТП (электронной торговой площадки).
 *
 * Передаёт проверенные поля из FormRequest в RegisterUserAction без «сырого» массива.
 */
readonly class RegisterUserDTO
{
    /**
     * @param string $inn ИНН (идентификационный номер налогоплательщика), логин участника
     * @param string $email Основной email для входа и подтверждения
     * @param string $password Пароль в открытом виде (хешируется моделью User)
     * @param EntityType $entityType Тип субъекта: юрлицо или физлицо
     * @param string $name Наименование организации или ФИО
     * @param string $phone Контактный телефон
     * @param string $directorName ФИО (фамилия, имя, отчество) руководителя
     * @param string|null $directorBirthDate Дата рождения руководителя (Y-m-d), если указана
     * @param string $contactPersons Контактные лица (текст)
     * @param list<string> $extraEmails Дополнительные email для рассылок
     * @param bool $pdConsent Согласие на обработку персональных данных
     */
    public function __construct(
        public string $inn,
        public string $email,
        public string $password,
        public EntityType $entityType,
        public string $name,
        public string $phone,
        public string $directorName,
        public ?string $directorBirthDate,
        public string $contactPersons,
        public array $extraEmails,
        public bool $pdConsent,
    ) {}

    /**
     * Собирает DTO из массива после валидации RegistrationRequest.
     *
     * @param array<string, mixed> $data Проверенные поля формы регистрации
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $entityType = $data['entity_type'] ?? EntityType::Legal;
        if (!$entityType instanceof EntityType) {
            $entityType = EntityType::from((string) $entityType);
        }

        /** @var list<string> $extraEmails */
        $extraEmails = array_values($data['extra_emails'] ?? []);

        return new self(
            inn: (string) ($data['inn'] ?? ''),
            email: (string) ($data['email'] ?? ''),
            password: (string) ($data['password'] ?? ''),
            entityType: $entityType,
            name: (string) ($data['name'] ?? ''),
            phone: (string) ($data['phone'] ?? ''),
            directorName: (string) ($data['director_name'] ?? ''),
            directorBirthDate: isset($data['director_birth_date'])
                ? (string) $data['director_birth_date']
                : null,
            contactPersons: (string) ($data['contact_persons'] ?? ''),
            extraEmails: $extraEmails,
            pdConsent: (bool) ($data['pd_consent'] ?? false),
        );
    }
}
