<?php

namespace App\Http\Controllers;

use App\Actions\Proposal\SendProposalMessageAction;
use App\Exceptions\DomainException;
use App\Http\Requests\Api\StoreProposalMessageRequest;
use App\Http\Resources\ProposalMessageResource;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Переписка по уточнению коммерческого предложения (КП).
 *
 * Фаза 6.4: участник (владелец) и администратор процедуры.
 * Маршруты участника: /api/proposals/{proposal}/messages
 */
class ProposalMessageController extends ApiController
{
    /**
     * История сообщений по своей заявке.
     *
     * @param Proposal $proposal Заявка
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException
     */
    public function index(Proposal $proposal): JsonResponse
    {
        $this->assertParticipantOwner($proposal);

        $messages = $proposal->messages()
            ->with('sender.profile')
            ->orderBy('id')
            ->get();

        return $this->success(
            ProposalMessageResource::collection($messages)->resolve(),
            'Переписка по заявке.',
        );
    }

    /**
     * Отправляет сообщение от участника.
     *
     * @param StoreProposalMessageRequest $request Текст сообщения
     * @param Proposal $proposal Заявка
     * @param SendProposalMessageAction $action Бизнес-операция
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException|DomainException
     */
    public function store(
        StoreProposalMessageRequest $request,
        Proposal $proposal,
        SendProposalMessageAction $action,
    ): JsonResponse {
        $this->assertParticipantOwner($proposal);

        /** @var User $sender */
        $sender = $request->user();
        $data = $request->validated();

        $message = $action->execute(
            proposal: $proposal->loadMissing('procedure'),
            sender: $sender,
            message: (string) $data['message'],
            attachments: $data['attachments'] ?? null,
            requestClarification: false,
        );

        return $this->created(
            new ProposalMessageResource($message),
            'Сообщение отправлено.',
        );
    }

    /**
     * Участник видит только свою заявку.
     *
     * @param Proposal $proposal Заявка
     * @return void
     *
     * @throws AccessDeniedHttpException
     */
    private function assertParticipantOwner(Proposal $proposal): void
    {
        /** @var User|null $user */
        $user = auth()->user();

        if ($user === null || (int) $proposal->user_id !== (int) $user->id) {
            throw new AccessDeniedHttpException('Доступна только переписка по своей заявке.');
        }
    }
}
