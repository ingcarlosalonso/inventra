<?php

namespace App\Reports;

use App\Models\Client;
use App\Models\Courier;
use App\Models\Order;
use App\Models\OrderState;
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
                'clients' => Client::orderBy('name')->get(['uuid', 'name'])->map(fn ($c) => ['id' => $c->uuid, 'name' => $c->name]),
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
        return Order::with(['client', 'orderState', 'courier'])
            ->whereBetween('created_at', [$from, $to])
            ->when(isset($filters['client_id']) && $filters['client_id'], fn ($q) => $q->whereHas('client', fn ($q2) => $q2->where('uuid', $filters['client_id'])))
            ->when(isset($filters['order_state_id']) && $filters['order_state_id'], fn ($q) => $q->whereHas('orderState', fn ($q2) => $q2->where('uuid', $filters['order_state_id'])))
            ->when(isset($filters['courier_id']) && $filters['courier_id'], fn ($q) => $q->whereHas('courier', fn ($q2) => $q2->where('uuid', $filters['courier_id'])))
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
