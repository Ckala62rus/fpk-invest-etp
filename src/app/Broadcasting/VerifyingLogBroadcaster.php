<?php

namespace App\Broadcasting;

use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Broadcasting\Broadcasters\UsePusherChannelConventions;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Драйвер до Laravel Reverb: пишет broadcast в лог и проверяет право на канал.
 *
 * Встроенные драйверы log и null не вызывают авторизацию каналов (auth пустой) —
 * для ЭТП (электронной торговой площадки) это нельзя использовать на /broadcasting/auth.
 */
class VerifyingLogBroadcaster extends Broadcaster
{
    use UsePusherChannelConventions;

    /**
     * @param LoggerInterface $logger Лог приложения
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Проверяет подписку Echo на private/presence канал.
     *
     * @param \Illuminate\Http\Request $request HTTP с channel_name и socket_id
     * @return mixed
     *
     * @throws AccessDeniedHttpException
     */
    public function auth($request)
    {
        $channelName = $this->normalizeChannelName((string) $request->channel_name);

        if ($request->channel_name === null || $request->channel_name === ''
            || ($this->isGuardedChannel((string) $request->channel_name)
                && ! $this->retrieveUser($request, $channelName))) {
            throw new AccessDeniedHttpException;
        }

        return parent::verifyUserCanAccessChannel($request, $channelName);
    }

    /**
     * Тело ответа для Laravel Echo (без реального подписи Pusher до фазы 12.2).
     *
     * @param \Illuminate\Http\Request $request Запрос auth
     * @param mixed $result true или массив presence
     * @return array<string, mixed>
     */
    public function validAuthenticationResponse($request, $result)
    {
        if (is_bool($result)) {
            return ['auth' => 'ok'];
        }

        $channelName = $this->normalizeChannelName((string) $request->channel_name);
        $user = $this->retrieveUser($request, $channelName);

        return [
            'auth' => 'ok',
            'channel_data' => [
                'user_id' => $user?->getAuthIdentifier(),
                'user_info' => $result,
            ],
        ];
    }

    /**
     * Пишет событие в лог вместо WebSocket-сервера.
     *
     * @param array<int, \Illuminate\Broadcasting\Channel|string> $channels Каналы
     * @param string $event Имя события
     * @param array<string, mixed> $payload Данные
     * @return void
     */
    public function broadcast(array $channels, $event, array $payload = []): void
    {
        $this->logger->debug('etp.broadcast', [
            'channels' => $this->formatChannels($channels),
            'event' => $event,
            'payload' => $payload,
        ]);
    }
}
