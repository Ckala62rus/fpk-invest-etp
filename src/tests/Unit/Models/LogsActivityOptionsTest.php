<?php

namespace Tests\Unit\Models;

use App\Models\AuctionBid;
use App\Models\Procedure;
use App\Models\User;
use Tests\TestCase;

/**
 * Проверяет настройки Spatie LogsActivity на критичных моделях.
 */
class LogsActivityOptionsTest extends TestCase
{
    /**
     * User не логирует пароль и remember_token, только dirty-изменения.
     */
    public function test_user_activity_log_excludes_secrets_and_logs_dirty_only(): void
    {
        $options = (new User())->getActivitylogOptions();

        $this->assertSame('user', $options->logName);
        $this->assertSame(['*'], $options->logAttributes);
        $this->assertContains('password', $options->logExceptAttributes);
        $this->assertContains('remember_token', $options->logExceptAttributes);
        $this->assertTrue($options->logOnlyDirty);
        $this->assertFalse($options->submitEmptyLogs);
    }

    /**
     * Procedure логирует все атрибуты, только изменившиеся, без пустых записей.
     */
    public function test_procedure_activity_log_is_dirty_only(): void
    {
        $options = (new Procedure())->getActivitylogOptions();

        $this->assertSame('procedure', $options->logName);
        $this->assertSame(['*'], $options->logAttributes);
        $this->assertTrue($options->logOnlyDirty);
        $this->assertFalse($options->submitEmptyLogs);
    }

    /**
     * AuctionBid логирует создание и отмену ставки.
     */
    public function test_auction_bid_activity_log_is_dirty_only(): void
    {
        $options = (new AuctionBid())->getActivitylogOptions();

        $this->assertSame('auction_bid', $options->logName);
        $this->assertSame(['*'], $options->logAttributes);
        $this->assertTrue($options->logOnlyDirty);
        $this->assertFalse($options->submitEmptyLogs);
    }
}
