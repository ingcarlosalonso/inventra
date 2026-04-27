<?php

namespace App\Reports;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ClientsReport
{
    public function getData(array $filters): array
    {
        [$from, $to] = $this->dateRange($filters);
        $rows = $this->buildQuery($from, $to)->get();

        $activeClients = $rows->where('sales_count', '>', 0)->count();

        return [
            'meta' => ['date_from' => $from->toDateString(), 'date_to' => $to->toDateString(), 'total' => $rows->count()],
            'kpis' => [
                'total_revenue' => (float) $rows->sum('total_revenue'),
                'total_sales' => (int) $rows->sum('sales_count'),
                'active_clients' => $activeClients,
                'total_clients' => $rows->count(),
            ],
            'chart' => $rows->take(10)->filter(fn ($r) => $r->sales_count > 0)->map(fn ($r) => [
                'name' => $r->name,
                'revenue' => (float) $r->total_revenue,
                'sales_count' => (int) $r->sales_count,
            ])->values(),
            'table' => $rows->map(fn ($r) => [
                'name' => $r->name,
                'email' => $r->email,
                'phone' => $r->phone,
                'sales_count' => (int) $r->sales_count,
                'total_revenue' => (float) ($r->total_revenue ?? 0),
                'avg_ticket' => round((float) ($r->avg_ticket ?? 0), 2),
                'last_sale_at' => $r->last_sale_at,
            ])->values(),
            'filters' => [],
        ];
    }

    public function getExportData(array $filters): array
    {
        [$from, $to] = $this->dateRange($filters);

        return $this->buildQuery($from, $to)
            ->get()
            ->map(fn ($r) => [
                $r->name,
                $r->email ?? '—',
                $r->phone ?? '—',
                (int) $r->sales_count,
                (float) ($r->total_revenue ?? 0),
                round((float) ($r->avg_ticket ?? 0), 2),
                $r->last_sale_at ? Carbon::parse($r->last_sale_at)->format('d/m/Y') : '—',
            ])->toArray();
    }

    public function getHeadings(): array
    {
        return ['Cliente', 'Email', 'Teléfono', 'Nro. ventas', 'Ingresos totales', 'Ticket promedio', 'Última venta'];
    }

    private function buildQuery(Carbon $from, Carbon $to)
    {
        return DB::connection('tenant')
            ->table('clients')
            ->select(
                'clients.uuid',
                'clients.name',
                'clients.email',
                'clients.phone',
                DB::raw('COUNT(sales.id) as sales_count'),
                DB::raw('SUM(sales.total) as total_revenue'),
                DB::raw('AVG(sales.total) as avg_ticket'),
                DB::raw('MAX(sales.created_at) as last_sale_at'),
            )
            ->leftJoin('sales', function ($join) use ($from, $to) {
                $join->on('sales.client_id', '=', 'clients.id')
                    ->whereNull('sales.deleted_at')
                    ->whereBetween('sales.created_at', [$from, $to]);
            })
            ->whereNull('clients.deleted_at')
            ->groupBy('clients.id', 'clients.uuid', 'clients.name', 'clients.email', 'clients.phone')
            ->orderByDesc('total_revenue');
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
