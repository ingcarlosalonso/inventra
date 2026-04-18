<?php

namespace App\Reports;

use App\Models\DailyCash;
use App\Models\PointOfSale;
use Illuminate\Support\Carbon;

class DailyCashesReport
{
    public function getData(array $filters): array
    {
        [$from, $to] = $this->dateRange($filters);
        $cashes = $this->buildQuery($filters, $from, $to)->get();

        return [
            'meta' => ['date_from' => $from->toDateString(), 'date_to' => $to->toDateString(), 'total' => $cashes->count()],
            'kpis' => [
                'count' => $cashes->count(),
                'closed_count' => $cashes->where('is_closed', true)->count(),
                'total_collected' => (float) $cashes->sum('payments_sum_amount'),
                'total_income' => (float) $cashes->sum('income_sum'),
                'total_expense' => (float) $cashes->sum('expense_sum'),
            ],
            'table' => $cashes->map(fn ($dc) => [
                'id' => $dc->uuid,
                'point_of_sale' => $dc->pointOfSale?->name ?? '—',
                'user' => $dc->user?->name ?? '—',
                'opening_balance' => (float) $dc->opening_balance,
                'collected' => (float) ($dc->payments_sum_amount ?? 0),
                'income' => (float) ($dc->income_sum ?? 0),
                'expense' => (float) ($dc->expense_sum ?? 0),
                'closing_balance' => (float) ($dc->closing_balance ?? 0),
                'status' => $dc->is_closed ? 'closed' : 'open',
                'opened_at' => $dc->opened_at?->toISOString(),
                'closed_at' => $dc->closed_at?->toISOString(),
            ])->values(),
            'filters' => [
                'pointsOfSale' => PointOfSale::orderBy('name')->get(['uuid', 'name'])->map(fn ($p) => ['id' => $p->uuid, 'name' => $p->name]),
            ],
        ];
    }

    public function getExportData(array $filters): array
    {
        [$from, $to] = $this->dateRange($filters);

        return $this->buildQuery($filters, $from, $to)
            ->get()
            ->map(fn ($dc) => [
                $dc->pointOfSale?->name ?? '—',
                $dc->user?->name ?? '—',
                (float) $dc->opening_balance,
                (float) ($dc->payments_sum_amount ?? 0),
                (float) ($dc->income_sum ?? 0),
                (float) ($dc->expense_sum ?? 0),
                (float) ($dc->closing_balance ?? 0),
                $dc->is_closed ? 'Cerrada' : 'Abierta',
                $dc->opened_at?->format('d/m/Y H:i'),
                $dc->closed_at?->format('d/m/Y H:i') ?? '—',
            ])->toArray();
    }

    public function getHeadings(): array
    {
        return ['Punto de venta', 'Usuario', 'Saldo inicial', 'Cobros', 'Ingresos extras', 'Egresos extras', 'Saldo cierre', 'Estado', 'Fecha apertura', 'Fecha cierre'];
    }

    private function buildQuery(array $filters, Carbon $from, Carbon $to)
    {
        return DailyCash::with(['pointOfSale', 'user'])
            ->withSum('payments', 'amount')
            ->withSum(['cashMovements as income_sum' => fn ($q) => $q->whereRelation('cashMovementType', 'is_income', true)], 'amount')
            ->withSum(['cashMovements as expense_sum' => fn ($q) => $q->whereRelation('cashMovementType', 'is_income', false)], 'amount')
            ->whereBetween('opened_at', [$from, $to])
            ->when(
                isset($filters['point_of_sale_id']) && $filters['point_of_sale_id'],
                fn ($q) => $q->whereHas('pointOfSale', fn ($q2) => $q2->where('uuid', $filters['point_of_sale_id']))
            )
            ->when(
                isset($filters['only_closed']) && $filters['only_closed'],
                fn ($q) => $q->where('is_closed', true)
            )
            ->orderByDesc('opened_at');
    }

    private function dateRange(array $filters): array
    {
        $from = isset($filters['date_from']) && $filters['date_from']
            ? Carbon::parse($filters['date_from'])->startOfDay()
            : now()->subDays(29)->startOfDay();

        $to = isset($filters['date_to']) && $filters['date_to']
            ? Carbon::parse($filters['date_to'])->endOfDay()
            : now()->endOfDay();

        return [$from, $to];
    }
}
