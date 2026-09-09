<?php

namespace App\Jobs;

use App\Enums\ReportFormat;
use App\Models\ReportRun;
use App\Models\ReportTemplate;
use App\Services\ReportQueryService;
use App\Support\LocalDiskPermissions;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\PhpWord;

/**
 * Асинхронная выгрузка отчёта в PDF / XLSX / DOC (фаза 10.3–10.6).
 */
class GenerateReportJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param int $runId ID запуска
     * @return void
     */
    public function __construct(
        public int $runId,
    ) {
    }

    /**
     * @param ReportQueryService $query Выборка
     * @return void
     */
    public function handle(ReportQueryService $query): void
    {
        $run = ReportRun::query()->with('template')->find($this->runId);
        if ($run === null || $run->template === null) {
            return;
        }

        /** @var ReportTemplate $template */
        $template = $run->template;
        $rows = $query->rows($template, $run->filters ?? []);
        $columns = $template->columns ?? array_keys($rows->first() ?? ['id' => null]);

        $dir = 'reports/'.$run->id;
        $path = match ($run->format) {
            ReportFormat::Xlsx => $this->writeXlsx($dir, $columns, $rows),
            ReportFormat::Doc => $this->writeDoc($dir, $template->name, $columns, $rows),
            default => $this->writePdf($dir, $template->name, $columns, $rows),
        };

        LocalDiskPermissions::ensureWebReadable(Storage::disk('local')->path($path));

        $run->update(['file_path' => $path]);
    }

    /**
     * @param string $dir Каталог
     * @param list<string> $columns Колонки
     * @param \Illuminate\Support\Collection<int, array<string, mixed>> $rows Строки
     * @return string
     */
    private function writeXlsx(string $dir, array $columns, $rows): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $col = 1;
        foreach ($columns as $name) {
            $sheet->setCellValue([$col, 1], $name);
            $col++;
        }
        $r = 2;
        foreach ($rows as $row) {
            $c = 1;
            foreach ($columns as $name) {
                $sheet->setCellValue([$c, $r], (string) ($row[$name] ?? ''));
                $c++;
            }
            $r++;
        }
        $path = $dir.'/report.xlsx';
        $full = Storage::disk('local')->path($path);
        Storage::disk('local')->makeDirectory($dir);
        (new Xlsx($spreadsheet))->save($full);

        return $path;
    }

    /**
     * @param string $dir Каталог
     * @param string $title Заголовок
     * @param list<string> $columns Колонки
     * @param \Illuminate\Support\Collection<int, array<string, mixed>> $rows Строки
     * @return string
     */
    private function writePdf(string $dir, string $title, array $columns, $rows): string
    {
        $html = '<h1>'.e($title).'</h1><table border="1" cellpadding="4" cellspacing="0"><tr>';
        foreach ($columns as $name) {
            $html .= '<th>'.e($name).'</th>';
        }
        $html .= '</tr>';
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($columns as $name) {
                $html .= '<td>'.e((string) ($row[$name] ?? '')).'</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</table>';
        $path = $dir.'/report.pdf';
        Storage::disk('local')->put($path, Pdf::loadHTML($html)->output());

        return $path;
    }

    /**
     * @param string $dir Каталог
     * @param string $title Заголовок
     * @param list<string> $columns Колонки
     * @param \Illuminate\Support\Collection<int, array<string, mixed>> $rows Строки
     * @return string
     */
    private function writeDoc(string $dir, string $title, array $columns, $rows): string
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        $section->addText($title);
        foreach ($rows as $row) {
            $line = [];
            foreach ($columns as $name) {
                $line[] = $name.': '.($row[$name] ?? '');
            }
            $section->addText(implode('; ', $line));
        }
        $path = $dir.'/report.docx';
        Storage::disk('local')->makeDirectory($dir);
        $phpWord->save(Storage::disk('local')->path($path));

        return $path;
    }
}
