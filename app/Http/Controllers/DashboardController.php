<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\DailyCash;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductPresentation;
use App\Models\Quote;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $today = now()->startOfDay();
        $thirtyDaysAgo = now()->subDays(29)->startOfDay();
        $sevenDaysAgo = now()->subDays(6)->startOfDay();

        return response()->json([
            'kpis' => $this->kpis($today),
            'sales_chart' => $this->salesChart($thirtyDaysAgo),
            'payment_methods' => $this->paymentMethods($thirtyDaysAgo),
            'order_states' => $this->orderStates(),
            'open_cashes' => $this->openCashes(),
            'recent_sales' => $this->recentSales(),
            'top_products' => $this->topProducts($thirtyDaysAgo),
            'low_stock' => $this->lowStock(),
            'weekly_comparison' => $this->weeklyComparison($sevenDaysAgo),
        ]);
    }

    private function kpis(Carbon $today): array
    {
        $todaySales = Sale::whereDate('created_at', today())->get();
        $monthStart = now()->startOfMonth();

        return [
            'today_revenue' => (float) $todaySales->sum('total'),
            'today_sales_count' => $todaySales->count(),
            'today_collected' => (float) Payment::whereDate('created_at', today())->sum('amount'),
            'month_revenue' => (float) Sale::where('created_at', '>=', $monthStart)->sum('total'),
            'month_sales_count' => Sale::where('created_at', '>=', $monthStart)->count(),
            'active_orders' => Order::whereHas('orderState', fn ($q) => $q->where('is_final_state', false))->count(),
            'pending_quotes' => Quote::whereNull('sale_id')->count(),
            'total_clients' => Client::count(),
            'low_stock_count' => ProductPresentation::whereColumn('stock', '<=', 'min_stock')
                ->where('is_active', true)
                ->count(),
            'open_cashes_count' => DailyCash::where('is_closed', false)->count(),
        ];
    }

    private function salesChart(Carbon $from): array
    {
        $rows = Sale::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(total) as revenue'),
            DB::raw('SUM(discount_amount) as discounts'),
        )
            ->where('created_at', '>=', $from)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        // Fill in missing days with zeros
        $filled = [];
        $cursor = $from->copy();
        $indexed = $rows->keyBy('date');

        while ($cursor->lte(now())) {
            $key = $cursor->toDateString();
            $row = $indexed->get($key);
            $filled[] = [
                'date' => $key,
                'count' => $row ? (int) $row->count : 0,
                'revenue' => $row ? (float) $row->revenue : 0,
                'discounts' => $row ? (float) $row->discounts : 0,
            ];
            $cursor->addDay();
        }

        return $filled;
    }

    private function paymentMethods(Carbon $from): array
    {
        return Payment::select('payment_methods.name', DB::raw('SUM(payments.amount) as total'), DB::raw('COUNT(*) as count'))
            ->join('payment_methods', 'payment_methods.id', '=', 'payments.payment_method_id')
            ->where('payments.created_at', '>=', $from)
            ->groupBy('payment_methods.id', 'payment_methods.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => [
                'name' => $r->name,
                'total' => (float) $r->total,
                'count' => (int) $r->count,
            ])
            ->toArray();
    }

    private function orderStates(): array
    {
        return Order::select('order_states.name', 'order_states.color', DB::raw('COUNT(*) as count'))
            ->join('order_states', 'order_states.id', '=', 'orders.order_state_id')
            ->groupBy('order_states.id', 'order_states.name', 'order_states.color')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($r) => [
                'name' => $r->name,
                'color' => $r->color ?? '#6366f1',
                'count' => (int) $r->count,
            ])
            ->toArray();
    }

    private function openCashes(): array
    {
        return DailyCash::with('pointOfSale')
            ->withSum('payments', 'amount')
            ->withSum(['cashMovements as income_sum' => fn ($q) => $q->whereRelation('cashMovementType', 'is_income', true)], 'amount')
            ->withSum(['cashMovements as expense_sum' => fn ($q) => $q->whereRelation('cashMovementType', 'is_income', false)], 'amount')
            ->where('is_closed', false)
            ->orderByDesc('opened_at')
            ->get()
            ->map(fn ($dc) => [
                'id' => $dc->uuid,
                'pos_name' => $dc->pointOfSale?->name ?? '—',
                'opening_balance' => (float) $dc->opening_balance,
                'current_balance' => round(
                    (float) $dc->opening_balance
                    + (float) ($dc->payments_sum_amount ?? 0)
                    + (float) ($dc->income_sum ?? 0)
                    - (float) ($dc->expense_sum ?? 0),
                    2
                ),
                'opened_at' => $dc->opened_at?->toISOString(),
            ])
            ->toArray();
    }

    private function recentSales(): array
    {
        return Sale::with(['client', 'saleState', 'payments'])
            ->orderByDesc('id')
            ->limit(8)
            ->get()
            ->map(fn ($s) => [
                'id' => $s->uuid,
                'client_name' => $s->client?->name ?? __('sales.no_client'),
                'total' => (float) $s->total,
                'paid_amount' => (float) $s->payments->sum('amount'),
                'state_name' => $s->saleState?->name,
                'state_color' => $s->saleState?->color ?? '#6366f1',
                'created_at' => $s->created_at->toISOString(),
            ])
            ->toArray();
    }

    private function topProducts(Carbon $from): array
    {
        return DB::connection('tenant')
            ->table('sale_items')
            ->select(
                'products.name as product_name',
                DB::raw('SUM(sale_items.quantity) as units_sold'),
                DB::raw('SUM(sale_items.total) as revenue'),
            )
            ->join('product_presentations', 'product_presentations.id', '=', 'sale_items.product_presentation_id')
            ->join('products', 'products.id', '=', 'product_presentations.product_id')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereNull('sales.deleted_at')
            ->where('sales.created_at', '>=', $from)
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get()
            ->map(fn ($r) => [
                'product_name' => $r->product_name,
                'units_sold' => (float) $r->units_sold,
                'revenue' => (float) $r->revenue,
            ])
            ->toArray();
    }

    private function lowStock(): array
    {
        return ProductPresentation::with(['product', 'presentation'])
            ->whereColumn('stock', '<=', 'min_stock')
            ->where('is_active', true)
            ->orderByRaw('stock - min_stock ASC')
            ->limit(5)
            ->get()
            ->map(fn ($pp) => [
                'product_name' => $pp->product?->name ?? '—',
                'presentation' => $pp->presentation?->name ?? '—',
                'stock' => (float) $pp->stock,
                'min_stock' => (float) $pp->min_stock,
            ])
            ->toArray();
    }

    private function weeklyComparison(Carbon $from): array
    {
        return Sale::select(
            DB::raw('DAYOFWEEK(created_at) as dow'),
            DB::raw('DAYNAME(created_at) as day_name'),
            DB::raw('SUM(total) as revenue'),
            DB::raw('COUNT(*) as count'),
        )
            ->where('created_at', '>=', $from)
            ->groupBy(DB::raw('DAYOFWEEK(created_at)'), DB::raw('DAYNAME(created_at)'))
            ->orderBy('dow')
            ->get()
            ->map(fn ($r) => [
                'day' => $r->day_name,
                'revenue' => (float) $r->revenue,
                'count' => (int) $r->count,
            ])
            ->toArray();
    }
}
