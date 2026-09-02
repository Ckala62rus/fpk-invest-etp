<?php

namespace Database\Seeders;

use App\Enums\NotificationEventType;
use App\Models\NotificationTemplate;
use App\Support\NotificationTemplateCode;
use Illuminate\Database\Seeder;

/**
 * Дефолтные шаблоны email-уведомлений ЭТП (фаза 7).
 */
class NotificationTemplateSeeder extends Seeder
{
    /**
     * @return void
     */
    public function run(): void
    {
        $templates = [
            [
                'code' => NotificationTemplateCode::ProcedurePublished,
                'name' => 'Публикация процедуры',
                'subject' => 'Опубликована процедура {{procedure.number}}',
                'body_html' => '<p>Опубликована процедура <strong>{{procedure.number}}</strong>: {{procedure.title}}.</p>',
                'event_type' => NotificationEventType::Event,
            ],
            [
                'code' => NotificationTemplateCode::UserApproved,
                'name' => 'Одобрение регистрации',
                'subject' => 'Регистрация на ЭТП одобрена',
                'body_html' => '<p>Здравствуйте! Ваша регистрация на ЭТП одобрена. Email: {{user.email}}.</p>',
                'event_type' => NotificationEventType::Event,
            ],
            [
                'code' => NotificationTemplateCode::ProcedureDocumentationChanged,
                'name' => 'Изменение документации',
                'subject' => 'Изменена документация {{procedure.number}}',
                'body_html' => '<p>Обновлена документация процедуры {{procedure.number}}. Новый срок приёма: {{procedure.ends_at}}.</p>',
                'event_type' => NotificationEventType::Event,
            ],
            [
                'code' => NotificationTemplateCode::BidCancelled,
                'name' => 'Отмена ставки',
                'subject' => 'Ставка отменена — {{procedure.number}}',
                'body_html' => '<p>Ваша ставка {{bid.amount}} по лоту {{lot.name}} отменена. Причина: {{bid.cancel_reason}}.</p>',
                'event_type' => NotificationEventType::Event,
            ],
            [
                'code' => NotificationTemplateCode::AuctionReminderOneDay,
                'name' => 'Напоминание об аукционе за 1 день',
                'subject' => 'Завтра аукцион {{procedure.number}}',
                'body_html' => '<p>Напоминаем: завтра начнётся аукцион {{procedure.number}} — {{procedure.title}}.</p>',
                'event_type' => NotificationEventType::Scheduled,
            ],
            [
                'code' => NotificationTemplateCode::AuctionReminderOneHour,
                'name' => 'Напоминание об аукционе за 1 час',
                'subject' => 'Через час аукцион {{procedure.number}}',
                'body_html' => '<p>Через час начнётся аукцион {{procedure.number}} — {{procedure.title}}.</p>',
                'event_type' => NotificationEventType::Scheduled,
            ],
            [
                'code' => NotificationTemplateCode::ExternalProcedureInvite,
                'name' => 'Внешнее приглашение на процедуру',
                'subject' => 'Приглашение на процедуру {{procedure.number}}',
                'body_html' => '<p>Вас приглашают принять участие в процедуре {{procedure.number}}: {{procedure.title}}.</p>',
                'event_type' => NotificationEventType::Event,
            ],
        ];

        foreach ($templates as $template) {
            NotificationTemplate::query()->updateOrCreate(
                ['code' => $template['code']],
                [
                    'name' => $template['name'],
                    'subject' => $template['subject'],
                    'body_html' => $template['body_html'],
                    'event_type' => $template['event_type'],
                    'is_active' => true,
                ],
            );
        }
    }
}
