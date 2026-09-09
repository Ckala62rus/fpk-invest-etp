<?php

namespace Tests\Feature\Admin;

use App\Jobs\SendAuctionInviteScheduleJob;
use App\Mail\RenderedNotificationMail;
use App\Models\ClassifierCategory;
use App\Models\Procedure;
use App\Models\User;
use Database\Seeders\NotificationTemplateSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Feature-тесты приглашений в день публикации аукциона (фаза 8.12).
 */
class SendAuctionInviteScheduleJobTest extends TestCase
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
     * Подписчик категории получает письмо в день публикации аукциона.
     *
     * @return void
     */
    public function test_sends_invite_on_publish_day(): void
    {
        Mail::fake();

        $category = ClassifierCategory::factory()->create();

        $subscriber = User::factory()->create(['email' => 'invitee@test.test']);
        $subscriber->assignRole('participant');
        $subscriber->categorySubscriptions()->sync([$category->id]);

        Procedure::factory()->auction()->create([
            'classifier_category_id' => $category->id,
            'published_at' => now(),
            'starts_at' => now()->addDays(3),
        ]);

        (new SendAuctionInviteScheduleJob())->handle(
            app(\App\Services\NotificationMailService::class),
            app(\App\Services\ProcedureNotificationRecipientService::class),
        );

        Mail::assertSent(RenderedNotificationMail::class, fn (RenderedNotificationMail $mail) => $mail->hasTo('invitee@test.test'));
    }
}
