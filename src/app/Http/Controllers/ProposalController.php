<?php

namespace App\Http\Controllers;

use App\Actions\Proposal\SubmitProposalAction;
use App\DTOs\SubmitProposalDTO;
use App\Exceptions\DomainException;
use App\Http\Requests\Api\SubmitProposalRequest;
use App\Http\Resources\ProposalResource;
use App\Models\Procedure;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Подача коммерческих предложений (КП) участником ЭТП.
 *
 * Фаза 6.1: POST /api/procedures/{procedure}/proposals.
 */
class ProposalController extends ApiController
{
    /**
     * Список своих коммерческих предложений участника.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $proposals = Proposal::query()
            ->where('user_id', $user->id)
            ->with(['procedure', 'admissionDecision'])
            ->orderByDesc('id')
            ->get();

        return $this->success(
            ProposalResource::collection($proposals)->resolve(),
            'Ваши коммерческие предложения.',
        );
    }

    /**
     * Подаёт коммерческое предложение по запросу предложений.
     *
     * @param SubmitProposalRequest $request Валидированные данные КП
     * @param Procedure $procedure Целевая ТЗП (торгово-закупочная процедура)
     * @param SubmitProposalAction $action Бизнес-операция подачи
     * @return JsonResponse
     *
     * @throws DomainException
     */
    public function store(
        SubmitProposalRequest $request,
        Procedure $procedure,
        SubmitProposalAction $action,
    ): JsonResponse {
        /** @var User $participant */
        $participant = $request->user();

        $proposal = $action->execute(
            SubmitProposalDTO::fromRequest($procedure, $participant, $request),
        );

        return $this->created(
            new ProposalResource($proposal),
            'Коммерческое предложение подано.',
        );
    }

    /**
     * Просмотр своей заявки участником (полное содержимое).
     *
     * @param Proposal $proposal Заявка
     * @return JsonResponse
     *
     * @throws AccessDeniedHttpException
     */
    public function show(Proposal $proposal): JsonResponse
    {
        /** @var User|null $user */
        $user = auth()->user();

        if ($user === null || (int) $proposal->user_id !== (int) $user->id) {
            throw new AccessDeniedHttpException('Доступна только своя заявка.');
        }

        $proposal->load(['fieldValues.customField', 'documents', 'admissionDecision']);

        return $this->success(
            new ProposalResource($proposal),
            'Ваша заявка.',
        );
    }
}
