<?php

namespace App\Http\Controllers;

use App\Actions\Proposal\SubmitProposalAction;
use App\DTOs\SubmitProposalDTO;
use App\Exceptions\DomainException;
use App\Http\Requests\Api\SubmitProposalRequest;
use App\Http\Resources\ProposalResource;
use App\Models\Procedure;
use App\Models\User;
use Illuminate\Http\JsonResponse;

/**
 * Подача коммерческих предложений (КП) участником ЭТП.
 *
 * Фаза 6.1: POST /api/procedures/{procedure}/proposals.
 */
class ProposalController extends ApiController
{
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
}
