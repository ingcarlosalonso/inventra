<?php

namespace App\Reports;

use App\Models\Client;
use App\Models\Courier;
use App\Models\Order;
use App\Models\Order\Scopes\ByClient as OrderByClient;
use App\Models\Order\Scopes\ByCourier as OrderByCourier;
use App\Models\Order\Scopes\ByDateRange as OrderByDateRange;
use App\Models\Order\Scopes\ByState as OrderByState;
use App\Models\OrderState;
use App\Models\Scopes\ByUuid;
use Illuminate\Support\Carbon;

class OrdersReport
{
    public function getData(array $filters): array
    {
        [$from, $to] = $this->dateRange($filters);
        $orders = $this->buildQuery($filters, $from, $to)->get();

        $totalRevenue = $orders->sum('total');
        $count = $orders->count();

        $byState = $orders->groupBy(fn ($o) => $o->orderState?->name ?? '—')
            ->map(fn ($group, $name) => [
                'name' => $name,
                'count' => $group->count(),
                'color' => $group->first()?->orderState?->color ?? '#6366f1',
            ])->values()->sortByDesc('count')->values();

        return [
            'meta' => ['date_from' => $from->toDateString(), 'date_to' => $to->toDateString(), 'total' => $count],
            'kpis' => [
                'count' => $count,
                'total_revenue' => (float) $totalRevenue,
                'avg_ticket' => $count > 0 ? round($totalRevenue / $count, 2) : 0,
                'states_count' => $byState->count(),
            ],
            'by_state' => $byState,
            'table' => $orders->map(fn ($o) => [
                'id' => $o->uuid,
                'client' => $o->client?->name ?? '—',
                'state' => $o->orderState?->name,
                'state_color' => $o->orderState?->color ?? '#6366f1',
                'courier' => $o->courier?->name ?? '—',
                'total' => (float) $o->total,
                'delivery_date' => $o->delivery_date?->toDateString(),
                'created_at' => $o->created_at->toISOString(),
            ])->values(),
            'filters' => [
                'clients' => Client::orderBy('first_name')->orderBy('last_name')->get(['uuid', 'first_name', 'last_name'])->map(fn ($c) => ['id' => $c->uuid, 'name' => $c->name]),
                'orderStates' => OrderState::orderBy('name')->get(['uuid', 'name'])->map(fn ($s) => ['id' => $s->uuid, 'name' => $s->name]),
                'couriers' => Courier::orderBy('name')->get(['uuid', 'name'])->map(fn ($c) => ['id' => $c->uuid, 'name' => $c->name]),
            ],
        ];
    }

    public function getExportData(array $filters): array
    {
        [$from, $to] = $this->dateRange($filters);

        return $this->buildQuery($filters, $from, $to)
            ->get()
            ->map(fn ($o) => [
                $o->created_at->format('d/m/Y H:i'),
                $o->client?->name ?? '—',
                $o->orderState?->name ?? '—',
                $o->courier?->name ?? '—',
                (float) $o->total,
                $o->delivery_date?->format('d/m/Y') ?? '—',
            ])->toArray();
    }

    public function getHeadings(): array
    {
        return ['Fecha', 'Cliente', 'Estado', 'Repartidor', 'Total', 'Fecha entrega'];
    }

    private function buildQuery(array $filters, Carbon $from, Carbon $to)
    {
        $clientId = isset($filters['client_id']) && $filters['client_id']
            ? Client::query()->withScopes(new ByUuid($filters['client_id']))->value('id')
            : null;

        $orderStateId = isset($filters['order_state_id']) && $filters['order_state_id']
            ? OrderState::query()->withScopes(new ByUuid($filters['order_state_id']))->value('id')
            : null;

        $courierId = isset($filters['courier_id']) && $filters['courier_id']
            ? Courier::query()->withScopes(new ByUuid($filters['courier_id']))->value('id')
            : null;

        return Order::with(['client', 'orderState', 'courier'])
            ->withScopes(new OrderByDateRange($from, $to))
            ->when($clientId, fn ($q) => $q->withScopes(new OrderByClient($clientId)))
            ->when($orderStateId, fn ($q) => $q->withScopes(new OrderByState($orderStateId)))
            ->when($courierId, fn ($q) => $q->withScopes(new OrderByCourier($courierId)))
            ->orderByDesc('created_at');
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
