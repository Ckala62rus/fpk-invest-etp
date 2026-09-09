<?php

namespace App\Jobs;

use App\Models\AuctionProtocol;
use App\Models\Procedure;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Формирует PDF-протокол аукциона (фаза 8.11).
 */
class GenerateAuctionProtocolJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param int $procedureId ID аукциона
     * @param int|null $generatedByUserId Администратор или null при автофинише
     * @return void
     */
    public function __construct(
        public int $procedureId,
        public ?int $generatedByUserId = null,
    ) {
    }

    /**
     * Пишет PDF на диск local и запись auction_protocols.
     *
     * @return void
     */
    public function handle(): void
    {
        $procedure = Procedure::query()
            ->with(['lots'])
            ->find($this->procedureId);

        if ($procedure === null) {
            return;
        }

        $pdf = Pdf::loadView('pdf.auction-protocol', [
            'procedure' => $procedure,
            'lots' => $procedure->lots,
            'generatedAt' => now()->toDateTimeString(),
        ]);

        $path = 'auction-protocols/'.$procedure->id.'/'.now()->format('YmdHis').'.pdf';
        Storage::disk('local')->put($path, $pdf->output());

        AuctionProtocol::query()->create([
            'procedure_id' => $procedure->id,
            'file_path' => $path,
            'generated_by' => $this->generatedByUserId,
            'generated_at' => now(),
            'template_version' => 1,
        ]);

        Log::channel('auction')->info('Сформирован PDF-протокол аукциона', [
            'procedure_id' => $procedure->id,
            'path' => $path,
        ]);
    }
}
