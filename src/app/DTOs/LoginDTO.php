<?php

namespace App\DTOs;

/**
 * DTO для входа в электронно-торговую площадку
 *
 * Получение логинаа ИНН и пароля
 */
readonly class LoginDTO
{
    public function __construct(
        public string  $inn,
        public ?string $password = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            inn: $data['inn'] ?? '',
            password: $data['password'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'inn' => $this->inn,
            'password' => $this->password,
        ];
    }
}
