<?php

namespace App\Support;

/**
 * Коды шаблонов email-уведомлений ЭТП (фаза 7).
 */
final class NotificationTemplateCode
{
    public const ProcedurePublished = 'procedure_published';

    public const UserApproved = 'user_approved';

    public const ProcedureDocumentationChanged = 'procedure_documentation_changed';

    public const BidCancelled = 'bid_cancelled';

    public const AuctionReminderOneDay = 'auction_reminder_1day';

    public const AuctionReminderOneHour = 'auction_reminder_1hour';

    public const ExternalProcedureInvite = 'external_procedure_invite';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::ProcedurePublished,
            self::UserApproved,
            self::ProcedureDocumentationChanged,
            self::BidCancelled,
            self::AuctionReminderOneDay,
            self::AuctionReminderOneHour,
            self::ExternalProcedureInvite,
        ];
    }
}
