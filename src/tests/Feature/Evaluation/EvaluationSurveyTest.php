<?php

namespace Tests\Feature\Evaluation;

use App\Jobs\CreateEvaluationSurveysJob;
use App\Mail\RenderedNotificationMail;
use App\Models\EvaluationSurvey;
use App\Models\EvaluationSurveyTemplate;
use App\Models\ParticipantRating;
use App\Models\Procedure;
use App\Models\ProcedureLot;
use App\Models\User;
use Database\Seeders\NotificationTemplateSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Feature-тесты опроса качества закупки (фаза 9).
 */
class EvaluationSurveyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(NotificationTemplateSeeder::class);
    }

    /**
     * Главный администратор создаёт вопрос опроса.
     *
     * @return void
     */
    public function test_super_admin_can_create_survey_question(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $this->actingAs($admin)
            ->postJson('/api/admin/evaluation-survey-templates', [
                'question' => 'Насколько вы довольны сроками поставки?',
                'field_type' => 'rating',
                'is_required' => true,
                'sort_order' => 1,
            ])
            ->assertCreated()
            ->assertJsonPath('data.field_type', 'rating');
    }

    /**
     * Администратор торгов не управляет шаблонами опроса.
     *
     * @return void
     */
    public function test_trade_admin_cannot_manage_survey_templates(): void
    {
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('trade_admin');

        $this->actingAs($admin)
            ->getJson('/api/admin/evaluation-survey-templates')
            ->assertForbidden();
    }

    /**
     * Job создаёт опрос через месяц после завершения и шлёт письмо.
     *
     * @return void
     */
    public function test_create_evaluation_surveys_job_sends_mail(): void
    {
        Mail::fake();

        $procedure = Procedure::factory()->completed()->create([
            'completed_at' => now()->subMonth()->subDay(),
            'customer_contact_email' => 'customer@test.test',
        ]);

        (new CreateEvaluationSurveysJob())->handle(app(\App\Services\NotificationMailService::class));

        $this->assertDatabaseHas('evaluation_surveys', [
            'procedure_id' => $procedure->id,
        ]);

        Mail::assertSent(RenderedNotificationMail::class, fn (RenderedNotificationMail $mail) => $mail->hasTo('customer@test.test'));
    }

    /**
     * Заказчик заполняет опрос по токену и ставит оценку победителю.
     *
     * @return void
     */
    public function test_guest_can_submit_evaluation_survey(): void
    {
        $winner = User::factory()->create();
        $winner->assignRole('participant');

        $procedure = Procedure::factory()->completed()->create();
        ProcedureLot::factory()->create([
            'procedure_id' => $procedure->id,
            'winner_user_id' => $winner->id,
        ]);

        $question = EvaluationSurveyTemplate::query()->create([
            'question' => 'Оцените качество работ',
            'field_type' => 'rating',
            'is_required' => true,
            'sort_order' => 0,
        ]);

        $survey = EvaluationSurvey::query()->create([
            'procedure_id' => $procedure->id,
            'token' => 'surveytoken123abc',
            'sent_at' => now(),
            'reminder_stage' => 0,
        ]);

        $this->getJson("/api/evaluation-surveys/{$survey->token}")
            ->assertOk()
            ->assertJsonPath('data.procedure_id', $procedure->id);

        $this->postJson("/api/evaluation-surveys/{$survey->token}", [
            'answers' => [
                ['question_id' => $question->id, 'value' => 5],
            ],
            'contractor_score' => 4,
            'product_score' => 5,
            'comment' => 'Работы выполнены в срок.',
        ])
            ->assertOk();

        $this->assertNotNull($survey->fresh()->completed_at);
        $this->assertDatabaseHas('participant_ratings', [
            'winner_user_id' => $winner->id,
            'procedure_id' => $procedure->id,
            'contractor_score' => 4,
        ]);
        $this->assertInstanceOf(ParticipantRating::class, ParticipantRating::query()->first());
    }
}
