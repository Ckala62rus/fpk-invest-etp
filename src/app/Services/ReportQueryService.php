<?php

namespace App\Services;

use App\Models\AuctionBid;
use App\Models\Procedure;
use App\Models\ReportTemplate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Выборка строк отчёта по query_config шаблона (фаза 10.2).
 *
 * Разрешённые source: procedures, auction_bids. Чужие ПДн в bids не включаем (нет email).
 */
class ReportQueryService
{
    /**
     * @param ReportTemplate $template Шаблон
     * @param array<string, mixed> $filters date_from, date_to, status
     * @return Collection<int, array<string, mixed>>
     */
    public function rows(ReportTemplate $template, array $filters = []): Collection
    {
        $config = $template->query_config ?? [];
        $source = $config['source'] ?? 'procedures';
        $columns = $template->columns ?? ['id'];

        $dateFrom = isset($filters['date_from']) ? Carbon::parse((string) $filters['date_from'])->startOfDay() : null;
        $dateTo = isset($filters['date_to']) ? Carbon::parse((string) $filters['date_to'])->endOfDay() : null;

        if ($source === 'auction_bids') {
            $query = AuctionBid::query()->orderBy('id');
            if ($dateFrom !== null) {
                $query->where('created_at', '>=', $dateFrom);
            }
            if ($dateTo !== null) {
                $query->where('created_at', '<=', $dateTo);
            }

            return $query->get()->map(function (AuctionBid $bid) use ($columns): array {
                $map = [
                    'id' => $bid->id,
                    'procedure_id' => $bid->procedure_id,
                    'lot_id' => $bid->lot_id,
                    'amount' => $bid->amount,
                    'is_cancelled' => $bid->is_cancelled,
                    'created_at' => $bid->created_at?->toIso8601String(),
                ];

                return $this->pick($map, $columns);
            });
        }

        $query = Procedure::query()->orderBy('id');
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if ($dateFrom !== null) {
            $query->where('created_at', '>=', $dateFrom);
        }
        if ($dateTo !== null) {
            $query->where('created_at', '<=', $dateTo);
        }

        return $query->get()->map(function (Procedure $procedure) use ($columns): array {
            $map = [
                'id' => $procedure->id,
                'number' => $procedure->number,
                'title' => $procedure->title,
                'status' => $procedure->status?->value,
                'type' => $procedure->type?->value,
                'starts_at' => $procedure->starts_at?->toIso8601String(),
                'ends_at' => $procedure->ends_at?->toIso8601String(),
                'completed_at' => $procedure->completed_at?->toIso8601String(),
            ];

            return $this->pick($map, $columns);
        });
    }

    /**
     * @param array<string, mixed> $map Строка
     * @param array<int, string> $columns Колонки
     * @return array<string, mixed>
     */
    private function pick(array $map, array $columns): array
    {
        $out = [];
        foreach ($columns as $column) {
            if (is_string($column) && array_key_exists($column, $map)) {
                $out[$column] = $map[$column];
            }
        }

        return $out === [] ? $map : $out;
    }
}
