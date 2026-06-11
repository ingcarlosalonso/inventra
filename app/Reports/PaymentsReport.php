<?php

namespace App\Reports;

use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PointOfSale;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PaymentsReport
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

        $query = Payment::with(['paymentMethod', 'dailyCash.pointOfSale'])
            ->whereBetween('created_at', [$from, $to]);

        if (! empty($filters['payment_method_id'])) {
            $query->whereHas('paymentMethod', fn ($q) => $q->where('uuid', $filters['payment_method_id']));
        }
        if (! empty($filters['point_of_sale_id'])) {
            $query->whereHas('dailyCash.pointOfSale', fn ($q) => $q->where('uuid', $filters['point_of_sale_id']));
        }

        $allPayments = $query->orderByDesc('created_at')->get();
        $totalAmount = $allPayments->sum('amount');
        $count = $allPayments->count();

        $byMethod = $allPayments->groupBy(fn ($p) => $p->paymentMethod?->name ?? '—')
            ->map(fn ($group, $name) => [
                'name' => $name,
                'total' => (float) $group->sum('amount'),
                'count' => $group->count(),
            ])->values()->sortByDesc('total')->values();

        $chartQuery = Payment::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(amount) as revenue'),
            DB::raw('COUNT(*) as count'),
        )
            ->whereBetween('created_at', [$from, $to])
            ->when(! empty($filters['payment_method_id']), fn ($q) => $q->whereHas('paymentMethod', fn ($q2) => $q2->where('uuid', $filters['payment_method_id'])))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        $paymentMethods = PaymentMethod::orderBy('name')->get(['uuid', 'name'])->map(fn ($pm) => ['id' => $pm->uuid, 'name' => $pm->name]);
        $pointsOfSale = PointOfSale::orderBy('name')->get(['uuid', 'name'])->map(fn ($p) => ['id' => $p->uuid, 'name' => $p->name]);

        return [
            'meta' => ['date_from' => $from->toDateString(), 'date_to' => $to->toDateString(), 'total' => $count],
            'kpis' => [
                'total_amount' => (float) $totalAmount,
                'count' => $count,
                'top_method' => $byMethod->first()['name'] ?? null,
                'avg_amount' => $count > 0 ? round($totalAmount / $count, 2) : 0,
            ],
            'by_method' => $byMethod,
            'chart' => $this->fillDailyChart($chartQuery, $from, $to),
            'table' => $allPayments->map(fn ($p) => [
                'date' => $p->created_at->toISOString(),
                'payment_method' => $p->paymentMethod?->name ?? '—',
                'amount' => (float) $p->amount,
                'point_of_sale' => $p->dailyCash?->pointOfSale?->name ?? '—',
            ])->values(),
            'filters' => compact('paymentMethods', 'pointsOfSale'),
        ];
    }

    public function getExportData(array $filters): array
    {
        [$from, $to] = $this->dateRange($filters);

        $payments = Payment::with(['paymentMethod', 'dailyCash.pointOfSale'])
            ->whereBetween('created_at', [$from, $to])
            ->when(! empty($filters['payment_method_id']), fn ($q) => $q->whereHas('paymentMethod', fn ($q2) => $q2->where('uuid', $filters['payment_method_id'])))
            ->when(! empty($filters['point_of_sale_id']), fn ($q) => $q->whereHas('dailyCash.pointOfSale', fn ($q2) => $q2->where('uuid', $filters['point_of_sale_id'])))
            ->orderByDesc('created_at')
            ->get();

        return $payments->map(fn ($p) => [
            $p->created_at->format('d/m/Y H:i'),
            $p->paymentMethod?->name ?? '—',
            (float) $p->amount,
            $p->dailyCash?->pointOfSale?->name ?? '—',
        ])->toArray();
    }

    public function getHeadings(): array
    {
        return ['Fecha', 'Método de pago', 'Monto', 'Punto de venta'];
    }
}
