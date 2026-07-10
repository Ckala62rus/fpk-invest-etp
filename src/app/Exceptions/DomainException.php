<?php

namespace App\Exceptions;

use Exception;

/**
 * Базовое доменное исключение API с HTTP-статусом и ошибками по полям.
 */
class DomainException extends Exception
{
    /**
     * @param string $message Сообщение для клиента
     * @param int $statusCode HTTP-статус ответа
     * @param array<string, array<int, string>|string> $errors Ошибки валидации/бизнес-правил
     * @param \Throwable|null $previous Предыдущее исключение
     */
    public function __construct(
        string $message,
        protected int $statusCode = 422,
        protected array $errors = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * HTTP-статус для JSON-ответа.
     *
     * @return int
     */
    public function statusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Ошибки по полям для JSON-ответа.
     *
     * @return array<string, array<int, string>|string>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
