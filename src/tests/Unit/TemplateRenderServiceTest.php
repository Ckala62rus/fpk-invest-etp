<?php

namespace Tests\Unit;

use App\Services\TemplateRenderService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Unit-тесты рендера плейсхолдеров шаблонов (фаза 7.2).
 */
class TemplateRenderServiceTest extends TestCase
{
    /**
     * @return TemplateRenderService
     */
    private function renderer(): TemplateRenderService
    {
        return new TemplateRenderService;
    }

    /**
     * @return array<string, array{0: string, 1: array<string, mixed>, 2: string}>
     */
    public static function renderCases(): array
    {
        return [
            'dot notation' => [
                'Процедура {{procedure.number}}',
                ['procedure' => ['number' => 'TZP-001']],
                'Процедура TZP-001',
            ],
            'missing key becomes empty' => [
                'Email: {{user.email}}',
                [],
                'Email: ',
            ],
            'boolean true' => [
                'Flag: {{flag}}',
                ['flag' => true],
                'Flag: 1',
            ],
            'boolean false' => [
                'Flag: {{flag}}',
                ['flag' => false],
                'Flag: 0',
            ],
            'multiple placeholders' => [
                '{{user.name}} — {{procedure.title}}',
                [
                    'user' => ['name' => 'Иван'],
                    'procedure' => ['title' => 'Закупка'],
                ],
                'Иван — Закупка',
            ],
        ];
    }

    /**
     * @param string $template Шаблон
     * @param array<string, mixed> $data Данные
     * @param string $expected Ожидаемый результат
     * @return void
     */
    #[DataProvider('renderCases')]
    public function test_render_replaces_placeholders(string $template, array $data, string $expected): void
    {
        $this->assertSame($expected, $this->renderer()->render($template, $data));
    }
}
