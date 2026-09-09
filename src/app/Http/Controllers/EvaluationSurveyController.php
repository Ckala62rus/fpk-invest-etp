<?php

namespace App\Http\Controllers;

use App\Exceptions\DomainException;
use App\Http\Requests\Api\StoreEvaluationResponseRequest;
use App\Http\Resources\EvaluationSurveyTemplateResource;
use App\Models\EvaluationResponse;
use App\Models\EvaluationSurvey;
use App\Models\EvaluationSurveyTemplate;
use App\Models\ParticipantRating;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Публичное заполнение опроса качества по токену (фаза 9.4–9.5).
 */
class EvaluationSurveyController extends ApiController
{
    /**
     * Показывает вопросы опроса.
     *
     * @param string $token Токен из письма
     * @return JsonResponse
     *
     * @throws DomainException
     */
    public function show(string $token): JsonResponse
    {
        $survey = $this->findOpenSurvey($token);
        $questions = EvaluationSurveyTemplate::query()->orderBy('sort_order')->orderBy('id')->get();

        return $this->success([
            'procedure_id' => $survey->procedure_id,
            'completed' => false,
            'questions' => EvaluationSurveyTemplateResource::collection($questions)->resolve(),
        ], 'Опрос качества закупки.');
    }

    /**
     * Сохраняет ответы и рейтинг победителя.
     *
     * @param StoreEvaluationResponseRequest $request Ответы
     * @param string $token Токен
     * @return JsonResponse
     *
     * @throws DomainException
     */
    public function store(StoreEvaluationResponseRequest $request, string $token): JsonResponse
    {
        $survey = $this->findOpenSurvey($token);
        $data = $request->validated();

        DB::transaction(function () use ($survey, $data): void {
            foreach ($data['answers'] as $row) {
                EvaluationResponse::query()->create([
                    'survey_id' => $survey->id,
                    'question_id' => $row['question_id'],
                    'answer' => ['value' => $row['value']],
                ]);
            }

            $survey->update(['completed_at' => now()]);

            $winnerId = $survey->procedure?->lots()->whereNotNull('winner_user_id')->value('winner_user_id');

            if ($winnerId !== null) {
                ParticipantRating::query()->create([
                    'winner_user_id' => (int) $winnerId,
                    'procedure_id' => $survey->procedure_id,
                    'contractor_score' => $data['contractor_score'] ?? null,
                    'product_score' => $data['product_score'] ?? null,
                    'comment' => $data['comment'] ?? null,
                ]);
            }
        });

        return $this->success(null, 'Опрос сохранён. Спасибо.');
    }

    /**
     * @param string $token Токен
     * @return EvaluationSurvey
     *
     * @throws DomainException
     */
    private function findOpenSurvey(string $token): EvaluationSurvey
    {
        $survey = EvaluationSurvey::query()
            ->with('procedure.lots')
            ->where('token', $token)
            ->first();

        if ($survey === null) {
            throw new DomainException('Опрос не найден.', 404);
        }

        if ($survey->completed_at !== null) {
            throw new DomainException('Опрос уже заполнен.', 422);
        }

        return $survey;
    }
}
