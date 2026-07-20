<?php

namespace App\Mail;

use App\Models\PasswordResetToken;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Письмо со ссылкой восстановления пароля пользователя ЭТП (электронной торговой площадки).
 */
class PasswordResetMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * Выпущенный токен восстановления пароля.
     *
     * @var PasswordResetToken
     */
    public PasswordResetToken $resetToken;

    /**
     * Создаёт письмо восстановления пароля.
     *
     * @param PasswordResetToken $resetToken Токен восстановления пароля
     * @return void
     */
    public function __construct(PasswordResetToken $resetToken)
    {
        $this->resetToken = $resetToken;
    }

    /**
     * Возвращает тему письма.
     *
     * @return Envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Восстановление пароля');
    }

    /**
     * Формирует содержимое письма с токеном для формы сброса пароля.
     *
     * @return Content
     */
    public function content(): Content
    {
        return new Content(htmlString: sprintf(
            '<p>Используйте токен для восстановления пароля: <strong>%s</strong></p>',
            e($this->resetToken->token),
        ));
    }
}
