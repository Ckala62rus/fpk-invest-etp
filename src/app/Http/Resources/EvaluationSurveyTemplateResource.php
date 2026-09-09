<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Вопрос шаблона опроса качества.
 *
 * @mixin \App\Models\EvaluationSurveyTemplate
 */
class EvaluationSurveyTemplateResource extends JsonResource
{
    /**
     * @param Request $request HTTP
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question' => $this->question,
            'field_type' => $this->field_type,
            'options' => $this->options,
            'is_required' => $this->is_required,
            'sort_order' => $this->sort_order,
            'conditional_logic' => $this->conditional_logic,
        ];
    }
}
