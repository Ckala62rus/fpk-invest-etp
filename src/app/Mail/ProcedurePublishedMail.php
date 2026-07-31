<?php

namespace App\Mail;

use App\Models\Procedure;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Уведомление о публикации новой ТЗП (торгово-закупочной процедуры).
 */
class ProcedurePublishedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * Опубликованная процедура.
     *
     * @var Procedure
     */
    public Procedure $procedure;

    /**
     * @param Procedure $procedure Опубликованная ТЗП
     * @return void
     */
    public function __construct(Procedure $procedure)
    {
        $this->procedure = $procedure;
    }

    /**
     * Тема письма.
     *
     * @return Envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Новая процедура на ЭТП: '.$this->procedure->number,
        );
    }

    /**
     * Шаблон письма (markdown).
     *
     * @return Content
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.procedure-published',
            with: [
                'number' => $this->procedure->number,
                'title' => $this->procedure->title,
                'typeLabel' => $this->procedure->type?->label(),
                'endsAt' => $this->procedure->ends_at?->format('d.m.Y H:i'),
            ],
        );
    }
}
