<?php

namespace Database\Seeders;

use App\Enums\AuctionMode;
use App\Enums\BidMode;
use App\Enums\CustomFieldScope;
use App\Enums\CustomFieldType;
use App\Enums\ParticipantStatus;
use App\Enums\ProcedureStatus;
use App\Enums\ProcedureType;
use App\Enums\ProcedureVisibility;
use App\Enums\ProposalStatus;
use App\Enums\TradeDirection;
use App\Enums\WinnerMode;
use App\Models\AuctionSetting;
use App\Models\ClassifierCategory;
use App\Models\Company;
use App\Models\Procedure;
use App\Models\ProcedureCustomField;
use App\Models\ProcedureLot;
use App\Models\ProcedureParticipant;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Демо-ТЗП (торгово-закупочные процедуры): черновик, открытый запрос КП, аукцион, закрытая, завершённая.
 *
 * Требует DemoCatalogSeeder и DemoUsersSeeder (trade_admin + participant).
 * Идемпотентный по фиксированным номерам DEMO-*.
 */
class DemoProceduresSeeder extends Seeder
{
    /**
     * Создаёт демо-процедуры, лоты, настройки аукциона, участника и одно КП.
     *
     * @return void
     */
    public function run(): void
    {
        $tradeAdmin = User::query()->where('inn', env('TRADE_ADMIN_INN', '770000000001'))->first();
        $participant = User::query()->where('inn', env('PARTICIPANT_INN', '770000000002'))->first();
        $company = Company::query()->where('inn', '7701000001')->first();
        $categorySmr = ClassifierCategory::query()
            ->where('name', 'СМР (строительно-монтажные работы)')
            ->first();
        $categoryIt = ClassifierCategory::query()
            ->where('name', 'ИТ (информационные технологии)')
            ->first();

        if ($tradeAdmin === null || $company === null || $categorySmr === null || $categoryIt === null) {
            $this->command?->warn(
                'DemoProceduresSeeder: нет trade_admin / компании / категорий — сначала DemoUsersSeeder и DemoCatalogSeeder.',
            );

            return;
        }

        $draft = $this->upsertProcedure(
            number: 'DEMO-RFP-DRAFT',
            attrs: [
                'title' => 'Черновик: ремонт офиса (демо)',
                'description' => 'Черновик ТЗП для проверки админки. Не виден на витрине.',
                'type' => ProcedureType::RequestForProposal,
                'status' => ProcedureStatus::Draft,
                'visibility' => ProcedureVisibility::Open,
                'classifier_category_id' => $categorySmr->id,
                'company_id' => $company->id,
                'responsible_user_id' => $tradeAdmin->id,
                'created_by' => $tradeAdmin->id,
                'published_at' => null,
                'starts_at' => now()->addDay(),
                'ends_at' => now()->addDays(14),
                'completed_at' => null,
                'results_published' => false,
            ],
        );

        $accepting = $this->upsertProcedure(
            number: 'DEMO-RFP-OPEN',
            attrs: [
                'title' => 'Запрос КП: поставка серверного оборудования (демо)',
                'description' => 'Открытый запрос коммерческих предложений. Виден на витрине и принимает КП.',
                'type' => ProcedureType::RequestForProposal,
                'status' => ProcedureStatus::Accepting,
                'visibility' => ProcedureVisibility::Open,
                'classifier_category_id' => $categoryIt->id,
                'company_id' => $company->id,
                'responsible_user_id' => $tradeAdmin->id,
                'created_by' => $tradeAdmin->id,
                'customer_contact_name' => 'Петров Пётр Петрович',
                'customer_contact_email' => 'procurement@fpk-invest.demo',
                'published_at' => now()->subDays(2),
                'starts_at' => now()->subDays(2),
                'ends_at' => now()->addDays(10),
                'completed_at' => null,
                'results_published' => false,
            ],
        );

        $this->ensureParticipantFields($accepting);

        $auction = $this->upsertProcedure(
            number: 'DEMO-AUCTION-1',
            attrs: [
                'title' => 'Аукцион: закупка кабельной продукции (демо)',
                'description' => 'Электронный аукцион на понижение с двумя лотами. Статус auction_pending.',
                'type' => ProcedureType::Auction,
                'status' => ProcedureStatus::AuctionPending,
                'visibility' => ProcedureVisibility::Open,
                'classifier_category_id' => $categorySmr->id,
                'company_id' => $company->id,
                'responsible_user_id' => $tradeAdmin->id,
                'created_by' => $tradeAdmin->id,
                'published_at' => now()->subDay(),
                'starts_at' => now()->addHours(2),
                'ends_at' => now()->addDays(3),
                'completed_at' => null,
                'results_published' => false,
            ],
        );

        $this->ensureAuctionSettings($auction);
        $this->ensureLots($auction, [
            [
                'sort_order' => 1,
                'name' => 'Кабель ВВГнг 3×2,5',
                'unit' => 'м',
                'quantity' => 5000,
                'start_price' => 850_000,
                'bid_step' => 8500,
            ],
            [
                'sort_order' => 2,
                'name' => 'Кабель ВВГнг 5×6',
                'unit' => 'м',
                'quantity' => 2000,
                'start_price' => 1_200_000,
                'bid_step' => 12_000,
            ],
        ]);

        $closed = $this->upsertProcedure(
            number: 'DEMO-RFP-CLOSED',
            attrs: [
                'title' => 'Закрытый запрос КП: консультации (демо)',
                'description' => 'Закрытая процедура — только приглашённые участники.',
                'type' => ProcedureType::RequestForProposal,
                'status' => ProcedureStatus::Accepting,
                'visibility' => ProcedureVisibility::Closed,
                'classifier_category_id' => $categoryIt->id,
                'company_id' => $company->id,
                'responsible_user_id' => $tradeAdmin->id,
                'created_by' => $tradeAdmin->id,
                'published_at' => now()->subDays(1),
                'starts_at' => now()->subDays(1),
                'ends_at' => now()->addDays(7),
                'completed_at' => null,
                'results_published' => false,
            ],
        );

        $completed = $this->upsertProcedure(
            number: 'DEMO-RFP-DONE',
            attrs: [
                'title' => 'Завершённый запрос КП: канцтовары (демо)',
                'description' => 'Завершённая процедура с опубликованными результатами.',
                'type' => ProcedureType::RequestForProposal,
                'status' => ProcedureStatus::Completed,
                'visibility' => ProcedureVisibility::Open,
                'classifier_category_id' => $categoryIt->id,
                'company_id' => $company->id,
                'responsible_user_id' => $tradeAdmin->id,
                'created_by' => $tradeAdmin->id,
                'published_at' => now()->subDays(30),
                'starts_at' => now()->subDays(30),
                'ends_at' => now()->subDays(5),
                'completed_at' => now()->subDays(3),
                'results_published' => true,
            ],
        );

        // Подавление unused — черновик и завершённая нужны на витрине/админке как есть.
        unset($draft, $completed);

        if ($participant !== null) {
            ProcedureParticipant::query()->updateOrCreate(
                [
                    'procedure_id' => $closed->id,
                    'user_id' => $participant->id,
                ],
                [
                    'status' => ParticipantStatus::Admitted,
                    'admitted_at' => now()->subDay(),
                    'admitted_by' => $tradeAdmin->id,
                    'rejection_reason' => null,
                ],
            );

            ProcedureParticipant::query()->updateOrCreate(
                [
                    'procedure_id' => $auction->id,
                    'user_id' => $participant->id,
                ],
                [
                    'status' => ParticipantStatus::Admitted,
                    'admitted_at' => now()->subHours(6),
                    'admitted_by' => $tradeAdmin->id,
                    'rejection_reason' => null,
                ],
            );

            Proposal::query()->updateOrCreate(
                [
                    'procedure_id' => $accepting->id,
                    'user_id' => $participant->id,
                    'version' => 1,
                ],
                [
                    'status' => ProposalStatus::Submitted,
                    'submitted_at' => now()->subHours(12),
                    'contract_form_agreed_at' => now()->subHours(12),
                    'parent_proposal_id' => null,
                ],
            );
        }
    }

    /**
     * Демо-поля участника для формы подачи КП.
     *
     * @param Procedure $procedure Процедура DEMO-RFP-OPEN
     * @return void
     */
    private function ensureParticipantFields(Procedure $procedure): void
    {
        ProcedureCustomField::query()->updateOrCreate(
            [
                'procedure_id' => $procedure->id,
                'label' => 'Срок поставки (дней)',
            ],
            [
                'scope' => CustomFieldScope::Participant,
                'field_type' => CustomFieldType::Number,
                'options' => null,
                'is_required' => true,
                'sort_order' => 1,
            ],
        );

        ProcedureCustomField::query()->updateOrCreate(
            [
                'procedure_id' => $procedure->id,
                'label' => 'Гарантия (мес.)',
            ],
            [
                'scope' => CustomFieldScope::Participant,
                'field_type' => CustomFieldType::Number,
                'options' => null,
                'is_required' => false,
                'sort_order' => 2,
            ],
        );

        ProcedureCustomField::query()->updateOrCreate(
            [
                'procedure_id' => $procedure->id,
                'label' => 'Комментарий к КП',
            ],
            [
                'scope' => CustomFieldScope::Participant,
                'field_type' => CustomFieldType::Text,
                'options' => null,
                'is_required' => false,
                'sort_order' => 3,
            ],
        );
    }

    /**
     * Создаёт или обновляет процедуру по фиксированному номеру.
     *
     * @param string $number Номер ТЗП
     * @param array<string, mixed> $attrs Поля модели
     * @return Procedure
     */
    private function upsertProcedure(string $number, array $attrs): Procedure
    {
        return Procedure::query()->updateOrCreate(
            ['number' => $number],
            array_merge([
                'trade_direction' => TradeDirection::Purchase,
                'storage_years' => 3,
                'source_procedure_id' => null,
                'deleted_by' => null,
            ], $attrs),
        );
    }

    /**
     * Гарантирует наличие настроек аукциона.
     *
     * @param Procedure $procedure Процедура-аукцион
     * @return void
     */
    private function ensureAuctionSettings(Procedure $procedure): void
    {
        AuctionSetting::query()->updateOrCreate(
            ['procedure_id' => $procedure->id],
            [
                'bid_mode' => BidMode::Standard,
                'auction_mode' => AuctionMode::Decrease,
                'extension_minutes' => 5,
                'extension_trigger_minutes' => null,
                'idle_timeout_minutes' => 30,
                'forbid_equal_bids' => true,
                'winner_mode' => WinnerMode::PerLot,
                'only_admitted_from_rfp' => false,
                'is_paused' => false,
                'paused_at' => null,
            ],
        );
    }

    /**
     * Создаёт или обновляет лоты аукциона по sort_order.
     *
     * @param Procedure $procedure Аукцион
     * @param list<array<string, mixed>> $lots Описание лотов
     * @return void
     */
    private function ensureLots(Procedure $procedure, array $lots): void
    {
        foreach ($lots as $lot) {
            ProcedureLot::query()->updateOrCreate(
                [
                    'procedure_id' => $procedure->id,
                    'sort_order' => $lot['sort_order'],
                ],
                [
                    'name' => $lot['name'],
                    'unit' => $lot['unit'],
                    'quantity' => $lot['quantity'],
                    'start_price' => $lot['start_price'],
                    'bid_step' => $lot['bid_step'],
                    'current_price' => null,
                    'winner_user_id' => null,
                ],
            );
        }
    }
}
