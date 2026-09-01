<?php

namespace App\Mail;

use App\Models\Procedure;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Приглашение незарегистрированного участника на процедуру (фаза 6.8).
 */
class ExternalProcedureInviteMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param Procedure $procedure Целевая ТЗП
     * @return void
     */
    public function __construct(
        public Procedure $procedure,
    ) {
    }

    /**
     * @return Envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Приглашение на закупку: '.$this->procedure->number,
        );
    }

    /**
     * @return Content
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.external-procedure-invite',
            with: [
                'procedure' => $this->procedure,
            ],
        );
    }
}
