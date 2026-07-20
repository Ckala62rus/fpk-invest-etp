<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * Письмо со ссылкой подтверждения email при регистрации на ЭТП (электронной торговой площадке).
 */
class VerifyEmailMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * Пользователь, подтверждающий адрес электронной почты.
     *
     * @var User
     */
    public User $user;

    /**
     * Создаёт письмо подтверждения email.
     *
     * @param User $user Пользователь, для которого сформирована ссылка
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Возвращает тему письма.
     *
     * @return Envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Подтверждение email');
    }

    /**
     * Формирует HTML-содержимое со временной подписанной ссылкой.
     *
     * @return Content
     */
    public function content(): Content
    {
        $url = URL::temporarySignedRoute(
            'auth.email.verify',
            now()->addMinutes(60),
            ['id' => $this->user->id, 'hash' => sha1($this->user->email)],
        );

        return new Content(htmlString: sprintf(
            '<p>Подтвердите email, перейдя по ссылке: <a href="%1$s">%1$s</a></p>',
            e($url),
        ));
    }
}
