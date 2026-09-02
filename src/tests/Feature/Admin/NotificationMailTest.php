<?php

namespace Tests\Feature\Admin;

use App\Enums\EmailSendStatus;
use App\Enums\ParticipantStatus;
use App\Enums\ProcedureVisibility;
use App\Events\BidCancelled;
use App\Events\UserApproved;
use App\Jobs\SendAuctionRemindersJob;
use App\Jobs\SendProcedurePublishedMailsJob;
use App\Mail\RenderedNotificationMail;
use App\Models\AuctionBid;
use App\Models\ClassifierCategory;
use App\Models\EmailSendLog;
use App\Models\Procedure;
use App\Models\ProcedureParticipant;
use App\Models\User;
use App\Support\NotificationTemplateCode;
use Database\Seeders\NotificationTemplateSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Feature-тесты отправки email по шаблонам (фаза 7.3–7.6).
 */
class NotificationMailTest extends TestCase
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
     * @return void
     */
    public function test_procedure_published_sends_mail_to_category_subscribers(): void
    {
        Mail::fake();

        $category = ClassifierCategory::factory()->create();

        /** @var User $subscriber */
        $subscriber = User::factory()->create(['email' => 'subscriber@test.test']);
        $subscriber->assignRole('participant');
        $subscriber->categorySubscriptions()->sync([$category->id]);

        /** @var User $other */
        $other = User::factory()->create(['email' => 'other@test.test']);
        $other->assignRole('participant');

        $procedure = Procedure::factory()->accepting()->create([
            'classifier_category_id' => $category->id,
            'visibility' => ProcedureVisibility::Open,
        ]);

        SendProcedurePublishedMailsJob::dispatchSync($procedure->id);

        Mail::assertSent(RenderedNotificationMail::class, fn (RenderedNotificationMail $mail) => $mail->hasTo('subscriber@test.test'));
        Mail::assertNotSent(RenderedNotificationMail::class, fn (RenderedNotificationMail $mail) => $mail->hasTo('other@test.test'));

        $this->assertDatabaseHas('email_send_logs', [
            'recipient_email' => 'subscriber@test.test',
            'status' => EmailSendStatus::Sent->value,
        ]);
    }

    /**
     * @return void
     */
    public function test_closed_procedure_sends_mail_to_participants_only(): void
    {
        Mail::fake();

        $procedure = Procedure::factory()->accepting()->create([
            'visibility' => ProcedureVisibility::Closed,
        ]);

        $participant = User::factory()->create(['email' => 'invited@test.test']);
        ProcedureParticipant::query()->create([
            'procedure_id' => $procedure->id,
            'user_id' => $participant->id,
            'status' => ParticipantStatus::Invited,
        ]);

        SendProcedurePublishedMailsJob::dispatchSync($procedure->id);

        Mail::assertSent(RenderedNotificationMail::class, fn (RenderedNotificationMail $mail) => $mail->hasTo('invited@test.test'));
    }

    /**
     * @return void
     */
    public function test_user_approved_event_sends_mail_and_logs(): void
    {
        Mail::fake();

        /** @var User $user */
        $user = User::factory()->create(['email' => 'newuser@test.test']);
        /** @var User&Authenticatable $admin */
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        UserApproved::dispatch($user, $admin);

        Mail::assertSent(RenderedNotificationMail::class, fn (RenderedNotificationMail $mail) => $mail->hasTo('newuser@test.test'));

        $this->assertDatabaseHas('email_send_logs', [
            'recipient_email' => 'newuser@test.test',
            'status' => EmailSendStatus::Sent->value,
        ]);
    }

    /**
     * @return void
     */
    public function test_bid_cancelled_event_sends_mail_to_bidder(): void
    {
        Mail::fake();

        $bid = AuctionBid::factory()->cancelled()->create();

        BidCancelled::dispatch($bid);

        Mail::assertSent(
            RenderedNotificationMail::class,
            fn (RenderedNotificationMail $mail) => $mail->hasTo($bid->user->email),
        );
    }

    /**
     * @return void
     */
    public function test_auction_reminder_job_sends_once_per_procedure(): void
    {
        Mail::fake();

        $procedure = Procedure::factory()->auction()->create([
            'starts_at' => now()->addDay(),
            'visibility' => ProcedureVisibility::Closed,
        ]);

        $participant = User::factory()->create(['email' => 'auction@test.test']);
        ProcedureParticipant::query()->create([
            'procedure_id' => $procedure->id,
            'user_id' => $participant->id,
            'status' => ParticipantStatus::Invited,
        ]);

        (new SendAuctionRemindersJob)->handle(
            app(\App\Services\NotificationMailService::class),
            app(\App\Services\ProcedureNotificationRecipientService::class),
        );

        Mail::assertSent(RenderedNotificationMail::class, fn (RenderedNotificationMail $mail) => $mail->hasTo('auction@test.test'));

        $count = EmailSendLog::query()
            ->whereHas('template', static fn ($q) => $q->where('code', NotificationTemplateCode::AuctionReminderOneDay))
            ->where('payload->procedure->id', $procedure->id)
            ->count();

        $this->assertSame(1, $count);

        Mail::fake();

        (new SendAuctionRemindersJob)->handle(
            app(\App\Services\NotificationMailService::class),
            app(\App\Services\ProcedureNotificationRecipientService::class),
        );

        Mail::assertNothingSent();
    }
}
