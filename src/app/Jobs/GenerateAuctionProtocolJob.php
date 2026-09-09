<?php

namespace App\Jobs;

use App\Models\AuctionProtocol;
use App\Models\Procedure;
use App\Support\LocalDiskPermissions;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Формирует PDF-протокол аукциона (фаза 8.11).
 *
 * Horizon и scheduler в Docker должны работать от www-data (как PHP-FPM) —
 * см. user в docker-compose.yml. LocalDiskPermissions — запасной chmod.
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
            ->with(['lots.winner.profile'])
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

        LocalDiskPermissions::ensureWebReadable(Storage::disk('local')->path($path));

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
