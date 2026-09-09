<?php

namespace Tests\Unit;

use App\Models\Procedure;
use App\Models\ReportTemplate;
use App\Models\User;
use App\Services\ReportQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Выборка строк отчёта по шаблону (фаза 10.2).
 */
class ReportQueryServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Возвращает колонки procedures из query_config.
     *
     * @return void
     */
    public function test_rows_for_procedures_source(): void
    {
        $admin = User::factory()->create();
        $procedure = Procedure::factory()->create(['title' => 'Отчётная ТЗП']);

        $template = ReportTemplate::query()->create([
            'name' => 'Процедуры',
            'query_config' => ['source' => 'procedures'],
            'columns' => ['id', 'title'],
            'created_by' => $admin->id,
        ]);

        $rows = app(ReportQueryService::class)->rows($template);

        $this->assertTrue($rows->contains(fn (array $row): bool => (int) $row['id'] === $procedure->id && $row['title'] === 'Отчётная ТЗП'));
    }
}
