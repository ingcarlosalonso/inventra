<?php

namespace App\Reports;

use App\Models\Reception;
use App\Models\Supplier;
use Illuminate\Support\Carbon;

class PurchasesReport
{
    public function getData(array $filters): array
    {
        [$from, $to] = $this->dateRange($filters);
        $receptions = $this->buildQuery($filters, $from, $to)->get();

        $totalCost = $receptions->sum('total');
        $count = $receptions->count();

        $bySupplier = $receptions->groupBy(fn ($r) => $r->supplier?->name ?? '—')
            ->map(fn ($group, $name) => [
                'name' => $name,
                'total' => (float) $group->sum('total'),
                'count' => $group->count(),
            ])->values()->sortByDesc('total')->values();

        return [
            'meta' => ['date_from' => $from->toDateString(), 'date_to' => $to->toDateString(), 'total' => $count],
            'kpis' => [
                'count' => $count,
                'total_cost' => (float) $totalCost,
                'unique_suppliers' => $bySupplier->count(),
                'avg_reception' => $count > 0 ? round($totalCost / $count, 2) : 0,
            ],
            'by_supplier' => $bySupplier,
            'table' => $receptions->map(fn ($r) => [
                'id' => $r->uuid,
                'supplier' => $r->supplier?->name ?? '—',
                'user' => $r->user?->name ?? '—',
                'total' => (float) $r->total,
                'notes' => $r->notes,
                'received_at' => $r->received_at?->toDateString(),
            ])->values(),
            'filters' => [
                'suppliers' => Supplier::orderBy('name')->get(['uuid', 'name'])->map(fn ($s) => ['id' => $s->uuid, 'name' => $s->name]),
            ],
        ];
    }

    public function getExportData(array $filters): array
    {
        [$from, $to] = $this->dateRange($filters);

        return $this->buildQuery($filters, $from, $to)
            ->get()
            ->map(fn ($r) => [
                $r->received_at?->format('d/m/Y') ?? '—',
                $r->supplier?->name ?? '—',
                $r->user?->name ?? '—',
                (float) $r->total,
                $r->notes ?? '',
            ])->toArray();
    }

    public function getHeadings(): array
    {
        return ['Fecha', 'Proveedor', 'Usuario', 'Total', 'Notas'];
    }

    private function buildQuery(array $filters, Carbon $from, Carbon $to)
    {
        return Reception::with(['supplier', 'user'])
            ->whereBetween('received_at', [$from, $to])
            ->when(
                isset($filters['supplier_id']) && $filters['supplier_id'],
                fn ($q) => $q->whereHas('supplier', fn ($q2) => $q2->where('uuid', $filters['supplier_id']))
            )
            ->orderByDesc('received_at');
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
