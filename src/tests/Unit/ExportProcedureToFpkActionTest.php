<?php

namespace Tests\Unit;

use App\Actions\Admin\ExportProcedureToFpkAction;
use App\Contracts\FpkExportClientInterface;
use App\Models\Procedure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit-тест заглушки экспорта на fpkinvest.ru (фаза 6.9).
 */
class ExportProcedureToFpkActionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return void
     */
    public function test_export_delegates_to_client(): void
    {
        $procedure = Procedure::factory()->create();
        $called = false;

        $client = new class($called) implements FpkExportClientInterface {
            public function __construct(private bool &$called)
            {
            }

            public function exportProcedure(Procedure $procedure): void
            {
                $this->called = true;
            }
        };

        $action = new ExportProcedureToFpkAction($client);
        $action->execute($procedure);

        $this->assertTrue($called);
    }
}
