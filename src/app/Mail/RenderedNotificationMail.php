<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Письмо из отрендеренного HTML-шаблона notification_templates.
 */
class RenderedNotificationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param string $subjectLine Тема письма
     * @param string $bodyHtml HTML-тело
     * @return void
     */
    public function __construct(
        public string $subjectLine,
        public string $bodyHtml,
    ) {
    }

    /**
     * @return Envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    /**
     * @return Content
     */
    public function content(): Content
    {
        return new Content(
            htmlString: $this->bodyHtml,
        );
    }
}
