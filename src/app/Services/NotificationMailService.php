<?php

namespace App\Services;

use App\Enums\EmailSendStatus;
use App\Mail\RenderedNotificationMail;
use App\Models\EmailSendLog;
use App\Models\NotificationTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Отправка email по шаблону с записью в email_send_logs (фаза 7.3 + 7.5).
 */
class NotificationMailService
{
    /**
     * @param TemplateRenderService $renderer Рендер плейсхолдеров
     * @return void
     */
    public function __construct(
        private readonly TemplateRenderService $renderer,
    ) {
    }

    /**
     * Отправляет письмо по коду шаблона; возвращает false, если шаблон не найден или неактивен.
     *
     * @param string $templateCode Код шаблона
     * @param string $recipientEmail Email получателя
     * @param array<string, mixed> $data Данные для плейсхолдеров
     * @param User|null $recipientUser Пользователь-получатель
     * @return bool Успешная отправка
     */
    public function send(
        string $templateCode,
        string $recipientEmail,
        array $data,
        ?User $recipientUser = null,
    ): bool {
        $template = NotificationTemplate::query()
            ->where('code', $templateCode)
            ->where('is_active', true)
            ->first();

        if ($template === null) {
            Log::warning('Notification template not found or inactive', [
                'code' => $templateCode,
                'email' => $recipientEmail,
            ]);

            return false;
        }

        $subject = $this->renderer->render($template->subject, $data);
        $body = $this->renderer->render($template->body_html, $data);

        $log = EmailSendLog::query()->create([
            'template_id' => $template->id,
            'recipient_email' => $recipientEmail,
            'user_id' => $recipientUser?->id,
            'subject' => $subject,
            'status' => EmailSendStatus::Pending,
            'payload' => $data,
        ]);

        try {
            Mail::to($recipientEmail)->send(new RenderedNotificationMail($subject, $body));

            $log->update([
                'status' => EmailSendStatus::Sent,
                'sent_at' => now(),
            ]);

            return true;
        } catch (Throwable $e) {
            $log->update([
                'status' => EmailSendStatus::Failed,
                'error' => $e->getMessage(),
            ]);

            Log::error('Failed to send notification email', [
                'template_code' => $templateCode,
                'email' => $recipientEmail,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * @param string $templateCode Код шаблона
     * @param iterable<User> $users Получатели с email
     * @param array<string, mixed> $data Общие данные
     * @return int Количество успешных отправок
     */
    public function sendToUsers(string $templateCode, iterable $users, array $data): int
    {
        $sent = 0;

        foreach ($users as $user) {
            if (! $user instanceof User || empty($user->email)) {
                continue;
            }

            $personalData = array_merge($data, [
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'inn' => $user->inn,
                    'name' => $user->profile?->name,
                ],
            ]);

            if ($this->send($templateCode, $user->email, $personalData, $user)) {
                $sent++;
            }
        }

        return $sent;
    }
}
