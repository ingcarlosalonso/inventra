<?php

namespace App\Reports;

use App\Models\Client;
use App\Models\PointOfSale;
use App\Models\Sale;
use App\Models\Sale\Scopes\ByClient as SaleByClient;
use App\Models\Sale\Scopes\ByDateRange as SaleByDateRange;
use App\Models\Sale\Scopes\ByPointOfSale as SaleByPointOfSale;
use App\Models\Sale\Scopes\ByState as SaleByState;
use App\Models\SaleState;
use App\Models\Scopes\ByUuid;
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

    /** @return array<string, int|null> */
    private function resolveFilterIds(array $filters): array
    {
        return [
            'client_id' => ! empty($filters['client_id'])
                ? Client::query()->withScopes(new ByUuid($filters['client_id']))->value('id')
                : null,
            'point_of_sale_id' => ! empty($filters['point_of_sale_id'])
                ? PointOfSale::query()->withScopes(new ByUuid($filters['point_of_sale_id']))->value('id')
                : null,
            'sale_state_id' => ! empty($filters['sale_state_id'])
                ? SaleState::query()->withScopes(new ByUuid($filters['sale_state_id']))->value('id')
                : null,
        ];
    }

    public function getData(array $filters): array
    {
        [$from, $to] = $this->dateRange($filters);
        $ids = $this->resolveFilterIds($filters);

        $query = Sale::with(['client', 'saleState', 'pointOfSale', 'payments'])
            ->withScopes(new SaleByDateRange($from, $to));

        if ($ids['client_id']) {
            $query->withScopes(new SaleByClient($ids['client_id']));
        }
        if ($ids['point_of_sale_id']) {
            $query->withScopes(new SaleByPointOfSale($ids['point_of_sale_id']));
        }
        if ($ids['sale_state_id']) {
            $query->withScopes(new SaleByState($ids['sale_state_id']));
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
            ->withScopes(new SaleByDateRange($from, $to))
            ->when($ids['client_id'], fn ($q) => $q->withScopes(new SaleByClient($ids['client_id'])))
            ->when($ids['point_of_sale_id'], fn ($q) => $q->withScopes(new SaleByPointOfSale($ids['point_of_sale_id'])))
            ->when($ids['sale_state_id'], fn ($q) => $q->withScopes(new SaleByState($ids['sale_state_id'])))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        $clients = Client::orderBy('first_name')->orderBy('last_name')->get(['uuid', 'first_name', 'last_name'])->map(fn ($c) => ['id' => $c->uuid, 'name' => $c->name]);
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
        $ids = $this->resolveFilterIds($filters);

        $sales = Sale::with(['client', 'saleState', 'pointOfSale', 'payments'])
            ->withScopes(new SaleByDateRange($from, $to))
            ->when($ids['client_id'], fn ($q) => $q->withScopes(new SaleByClient($ids['client_id'])))
            ->when($ids['point_of_sale_id'], fn ($q) => $q->withScopes(new SaleByPointOfSale($ids['point_of_sale_id'])))
            ->when($ids['sale_state_id'], fn ($q) => $q->withScopes(new SaleByState($ids['sale_state_id'])))
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
