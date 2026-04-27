<?php

namespace App\Reports;

use App\Models\Client;
use App\Models\PointOfSale;
use App\Models\Sale;
use App\Models\SaleState;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SalesReport
{
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

    private function fillDailyChart($chart, Carbon $from, Carbon $to): array
    {
        $filled = [];
        $cursor = $from->copy()->startOfDay();
        $indexed = $chart->keyBy('date');

        while ($cursor->lte($to)) {
            $key = $cursor->toDateString();
            $row = $indexed->get($key);
            $filled[] = [
                'date' => $key,
                'count' => $row ? (int) $row->count : 0,
                'revenue' => $row ? (float) $row->revenue : 0,
                'discounts' => isset($row->discounts) ? (float) $row->discounts : 0,
            ];
            $cursor->addDay();
        }

        return $filled;
    }

    public function getData(array $filters): array
    {
        [$from, $to] = $this->dateRange($filters);

        $query = Sale::with(['client', 'saleState', 'pointOfSale', 'payments'])
            ->whereBetween('created_at', [$from, $to]);

        if (! empty($filters['client_id'])) {
            $query->whereHas('client', fn ($q) => $q->where('uuid', $filters['client_id']));
        }
        if (! empty($filters['point_of_sale_id'])) {
            $query->whereHas('pointOfSale', fn ($q) => $q->where('uuid', $filters['point_of_sale_id']));
        }
        if (! empty($filters['sale_state_id'])) {
            $query->whereHas('saleState', fn ($q) => $q->where('uuid', $filters['sale_state_id']));
        }

        $sales = $query->orderByDesc('created_at')->get();

        $totalRevenue = $sales->sum('total');
        $totalCollected = $sales->flatMap->payments->sum('amount');
        $totalDiscounts = $sales->sum('discount_amount');
        $count = $sales->count();

        $chartQuery = Sale::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(total) as revenue'),
            DB::raw('SUM(discount_amount) as discounts'),
        )
            ->whereBetween('created_at', [$from, $to])
            ->when(! empty($filters['client_id']), fn ($q) => $q->whereHas('client', fn ($q2) => $q2->where('uuid', $filters['client_id'])))
            ->when(! empty($filters['point_of_sale_id']), fn ($q) => $q->whereHas('pointOfSale', fn ($q2) => $q2->where('uuid', $filters['point_of_sale_id'])))
            ->when(! empty($filters['sale_state_id']), fn ($q) => $q->whereHas('saleState', fn ($q2) => $q2->where('uuid', $filters['sale_state_id'])))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        $clients = Client::orderBy('name')->get(['uuid', 'name'])->map(fn ($c) => ['id' => $c->uuid, 'name' => $c->name]);
        $pointsOfSale = PointOfSale::orderBy('name')->get(['uuid', 'name'])->map(fn ($p) => ['id' => $p->uuid, 'name' => $p->name]);
        $saleStates = SaleState::orderBy('name')->get(['uuid', 'name'])->map(fn ($s) => ['id' => $s->uuid, 'name' => $s->name]);

        return [
            'meta' => ['date_from' => $from->toDateString(), 'date_to' => $to->toDateString(), 'total' => $count],
            'kpis' => [
                'total_revenue' => (float) $totalRevenue,
                'total_collected' => (float) $totalCollected,
                'total_discounts' => (float) $totalDiscounts,
                'count' => $count,
                'avg_ticket' => $count > 0 ? round($totalRevenue / $count, 2) : 0,
            ],
            'chart' => $this->fillDailyChart($chartQuery, $from, $to),
            'table' => $sales->map(fn ($s) => [
                'id' => $s->uuid,
                'client' => $s->client?->name ?? __('sales.no_client'),
                'point_of_sale' => $s->pointOfSale?->name,
                'state' => $s->saleState?->name,
                'state_color' => $s->saleState?->color ?? '#6366f1',
                'subtotal' => (float) $s->subtotal,
                'discount_amount' => (float) $s->discount_amount,
                'total' => (float) $s->total,
                'collected' => (float) $s->payments->sum('amount'),
                'created_at' => $s->created_at->toISOString(),
            ])->values(),
            'filters' => compact('clients', 'pointsOfSale', 'saleStates'),
        ];
    }

    public function getExportData(array $filters): array
    {
        [$from, $to] = $this->dateRange($filters);

        $sales = Sale::with(['client', 'saleState', 'pointOfSale', 'payments'])
            ->whereBetween('created_at', [$from, $to])
            ->when(! empty($filters['client_id']), fn ($q) => $q->whereHas('client', fn ($q2) => $q2->where('uuid', $filters['client_id'])))
            ->when(! empty($filters['point_of_sale_id']), fn ($q) => $q->whereHas('pointOfSale', fn ($q2) => $q2->where('uuid', $filters['point_of_sale_id'])))
            ->when(! empty($filters['sale_state_id']), fn ($q) => $q->whereHas('saleState', fn ($q2) => $q2->where('uuid', $filters['sale_state_id'])))
            ->orderByDesc('created_at')
            ->get();

        return $sales->map(fn ($s) => [
            $s->created_at->format('d/m/Y H:i'),
            $s->client?->name ?? __('sales.no_client'),
            $s->pointOfSale?->name ?? '—',
            $s->saleState?->name ?? '—',
            (float) $s->subtotal,
            (float) $s->discount_amount,
            (float) $s->total,
            (float) $s->payments->sum('amount'),
        ])->toArray();
    }

    public function getHeadings(): array
    {
        return ['Fecha', 'Cliente', 'Punto de venta', 'Estado', 'Subtotal', 'Descuento', 'Total', 'Cobrado'];
    }
}
