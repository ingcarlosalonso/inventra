<?php

namespace App\Http\Controllers;

use App\Exports\ReportExport;
use App\Models\Client;
use App\Models\Courier;
use App\Models\DailyCash;
use App\Models\Order;
use App\Models\OrderState;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PointOfSale;
use App\Models\ProductPresentation;
use App\Models\ProductType;
use App\Models\Reception;
use App\Models\Sale;
use App\Models\SaleState;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    private function dateRange(Request $request): array
    {
        $from = $request->filled('date_from')
            ? Carbon::parse($request->string('date_from'))->startOfDay()
            : now()->subDays(29)->startOfDay();

        $to = $request->filled('date_to')
            ? Carbon::parse($request->string('date_to'))->endOfDay()
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

    // ── SALES ─────────────────────────────────────────────────────────────────

    public function sales(Request $request): JsonResponse
    {
        [$from, $to] = $this->dateRange($request);

        $query = Sale::with(['client', 'saleState', 'pointOfSale', 'payments'])
            ->whereBetween('created_at', [$from, $to]);

        if ($request->filled('client_id')) {
            $query->whereHas('client', fn ($q) => $q->where('uuid', $request->string('client_id')));
        }
        if ($request->filled('point_of_sale_id')) {
            $query->whereHas('pointOfSale', fn ($q) => $q->where('uuid', $request->string('point_of_sale_id')));
        }
        if ($request->filled('sale_state_id')) {
            $query->whereHas('saleState', fn ($q) => $q->where('uuid', $request->string('sale_state_id')));
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
            ->when($request->filled('client_id'), fn ($q) => $q->whereHas('client', fn ($q2) => $q2->where('uuid', $request->string('client_id'))))
            ->when($request->filled('point_of_sale_id'), fn ($q) => $q->whereHas('pointOfSale', fn ($q2) => $q2->where('uuid', $request->string('point_of_sale_id'))))
            ->when($request->filled('sale_state_id'), fn ($q) => $q->whereHas('saleState', fn ($q2) => $q2->where('uuid', $request->string('sale_state_id'))))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        $clients = Client::orderBy('name')->get(['uuid', 'name'])->map(fn ($c) => ['id' => $c->uuid, 'name' => $c->name]);
        $pointsOfSale = PointOfSale::orderBy('name')->get(['uuid', 'name'])->map(fn ($p) => ['id' => $p->uuid, 'name' => $p->name]);
        $saleStates = SaleState::orderBy('name')->get(['uuid', 'name'])->map(fn ($s) => ['id' => $s->uuid, 'name' => $s->name]);

        return response()->json([
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
        ]);
    }

    public function salesExport(Request $request): BinaryFileResponse
    {
        [$from, $to] = $this->dateRange($request);

        $sales = Sale::with(['client', 'saleState', 'pointOfSale', 'payments'])
            ->whereBetween('created_at', [$from, $to])
            ->when($request->filled('client_id'), fn ($q) => $q->whereHas('client', fn ($q2) => $q2->where('uuid', $request->string('client_id'))))
            ->when($request->filled('point_of_sale_id'), fn ($q) => $q->whereHas('pointOfSale', fn ($q2) => $q2->where('uuid', $request->string('point_of_sale_id'))))
            ->when($request->filled('sale_state_id'), fn ($q) => $q->whereHas('saleState', fn ($q2) => $q2->where('uuid', $request->string('sale_state_id'))))
            ->orderByDesc('created_at')
            ->get();

        $headings = ['Fecha', 'Cliente', 'Punto de venta', 'Estado', 'Subtotal', 'Descuento', 'Total', 'Cobrado'];
        $data = $sales->map(fn ($s) => [
            $s->created_at->format('d/m/Y H:i'),
            $s->client?->name ?? __('sales.no_client'),
            $s->pointOfSale?->name ?? '—',
            $s->saleState?->name ?? '—',
            (float) $s->subtotal,
            (float) $s->discount_amount,
            (float) $s->total,
            (float) $s->payments->sum('amount'),
        ])->toArray();

        return Excel::download(new ReportExport($data, $headings), 'reporte-ventas-'.now()->format('Y-m-d').'.xlsx');
    }

    // ── PRODUCTS ──────────────────────────────────────────────────────────────

    public function products(Request $request): JsonResponse
    {
        [$from, $to] = $this->dateRange($request);

        $query = DB::connection('tenant')
            ->table('sale_items')
            ->select(
                'products.id as product_id',
                'products.name as product_name',
                'product_types.name as product_type',
                DB::raw('SUM(sale_items.quantity) as units_sold'),
                DB::raw('SUM(sale_items.total) as revenue'),
                DB::raw('AVG(sale_items.unit_price) as avg_price'),
                DB::raw('COUNT(DISTINCT sale_items.sale_id) as sale_count'),
            )
            ->join('product_presentations', 'product_presentations.id', '=', 'sale_items.product_presentation_id')
            ->join('products', 'products.id', '=', 'product_presentations.product_id')
            ->leftJoin('product_types', 'product_types.id', '=', 'products.product_type_id')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereNull('sales.deleted_at')
            ->whereNull('sale_items.deleted_at')
            ->whereBetween('sales.created_at', [$from, $to]);

        if ($request->filled('product_type_id')) {
            $productTypeId = ProductType::where('uuid', $request->string('product_type_id'))->value('id');
            if ($productTypeId) {
                $query->where('products.product_type_id', $productTypeId);
            }
        }

        $rows = $query
            ->groupBy('products.id', 'products.name', 'product_types.name')
            ->orderByDesc('revenue')
            ->get();

        $productTypes = ProductType::orderBy('name')->get(['uuid', 'name'])->map(fn ($pt) => ['id' => $pt->uuid, 'name' => $pt->name]);

        return response()->json([
            'meta' => ['date_from' => $from->toDateString(), 'date_to' => $to->toDateString(), 'total' => $rows->count()],
            'kpis' => [
                'total_revenue' => (float) $rows->sum('revenue'),
                'total_units' => (float) $rows->sum('units_sold'),
                'unique_products' => $rows->count(),
                'top_product' => $rows->first()?->product_name,
            ],
            'chart' => $rows->take(10)->map(fn ($r) => [
                'name' => $r->product_name,
                'revenue' => (float) $r->revenue,
                'units_sold' => (float) $r->units_sold,
            ])->values(),
            'table' => $rows->map(fn ($r) => [
                'product_name' => $r->product_name,
                'product_type' => $r->product_type,
                'units_sold' => (float) $r->units_sold,
                'revenue' => (float) $r->revenue,
                'avg_price' => round((float) $r->avg_price, 2),
                'sale_count' => (int) $r->sale_count,
            ])->values(),
            'filters' => compact('productTypes'),
        ]);
    }

    public function productsExport(Request $request): BinaryFileResponse
    {
        [$from, $to] = $this->dateRange($request);

        $rows = DB::connection('tenant')
            ->table('sale_items')
            ->select(
                'products.name as product_name',
                'product_types.name as product_type',
                DB::raw('SUM(sale_items.quantity) as units_sold'),
                DB::raw('SUM(sale_items.total) as revenue'),
                DB::raw('AVG(sale_items.unit_price) as avg_price'),
                DB::raw('COUNT(DISTINCT sale_items.sale_id) as sale_count'),
            )
            ->join('product_presentations', 'product_presentations.id', '=', 'sale_items.product_presentation_id')
            ->join('products', 'products.id', '=', 'product_presentations.product_id')
            ->leftJoin('product_types', 'product_types.id', '=', 'products.product_type_id')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereNull('sales.deleted_at')
            ->whereNull('sale_items.deleted_at')
            ->whereBetween('sales.created_at', [$from, $to])
            ->when($request->filled('product_type_id'), function ($q) use ($request) {
                $id = ProductType::where('uuid', $request->string('product_type_id'))->value('id');
                if ($id) {
                    $q->where('products.product_type_id', $id);
                }
            })
            ->groupBy('products.id', 'products.name', 'product_types.name')
            ->orderByDesc('revenue')
            ->get();

        $headings = ['Producto', 'Categoría', 'Unidades vendidas', 'Ingresos', 'Precio promedio', 'Nro. ventas'];
        $data = $rows->map(fn ($r) => [
            $r->product_name,
            $r->product_type ?? '—',
            (float) $r->units_sold,
            (float) $r->revenue,
            round((float) $r->avg_price, 2),
            (int) $r->sale_count,
        ])->toArray();

        return Excel::download(new ReportExport($data, $headings), 'reporte-productos-'.now()->format('Y-m-d').'.xlsx');
    }

    // ── PAYMENTS ──────────────────────────────────────────────────────────────

    public function payments(Request $request): JsonResponse
    {
        [$from, $to] = $this->dateRange($request);

        $query = Payment::with(['paymentMethod', 'dailyCash.pointOfSale'])
            ->whereBetween('created_at', [$from, $to]);

        if ($request->filled('payment_method_id')) {
            $query->whereHas('paymentMethod', fn ($q) => $q->where('uuid', $request->string('payment_method_id')));
        }
        if ($request->filled('point_of_sale_id')) {
            $query->whereHas('dailyCash.pointOfSale', fn ($q) => $q->where('uuid', $request->string('point_of_sale_id')));
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
            ->when($request->filled('payment_method_id'), fn ($q) => $q->whereHas('paymentMethod', fn ($q2) => $q2->where('uuid', $request->string('payment_method_id'))))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        $paymentMethods = PaymentMethod::orderBy('name')->get(['uuid', 'name'])->map(fn ($pm) => ['id' => $pm->uuid, 'name' => $pm->name]);
        $pointsOfSale = PointOfSale::orderBy('name')->get(['uuid', 'name'])->map(fn ($p) => ['id' => $p->uuid, 'name' => $p->name]);

        return response()->json([
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
        ]);
    }

    public function paymentsExport(Request $request): BinaryFileResponse
    {
        [$from, $to] = $this->dateRange($request);

        $payments = Payment::with(['paymentMethod', 'dailyCash.pointOfSale'])
            ->whereBetween('created_at', [$from, $to])
            ->when($request->filled('payment_method_id'), fn ($q) => $q->whereHas('paymentMethod', fn ($q2) => $q2->where('uuid', $request->string('payment_method_id'))))
            ->when($request->filled('point_of_sale_id'), fn ($q) => $q->whereHas('dailyCash.pointOfSale', fn ($q2) => $q2->where('uuid', $request->string('point_of_sale_id'))))
            ->orderByDesc('created_at')
            ->get();

        $headings = ['Fecha', 'Método de pago', 'Monto', 'Punto de venta'];
        $data = $payments->map(fn ($p) => [
            $p->created_at->format('d/m/Y H:i'),
            $p->paymentMethod?->name ?? '—',
            (float) $p->amount,
            $p->dailyCash?->pointOfSale?->name ?? '—',
        ])->toArray();

        return Excel::download(new ReportExport($data, $headings), 'reporte-cobros-'.now()->format('Y-m-d').'.xlsx');
    }

    // ── INVENTORY ─────────────────────────────────────────────────────────────

    public function inventory(Request $request): JsonResponse
    {
        $query = ProductPresentation::with(['product.productType', 'presentation'])
            ->where('is_active', true);

        if ($request->filled('product_type_id')) {
            $query->whereHas('product.productType', fn ($q) => $q->where('uuid', $request->string('product_type_id')));
        }

        $stockStatus = $request->string('stock_status', 'all');
        if ($stockStatus === 'low') {
            $query->whereColumn('stock', '<=', 'min_stock')->where('stock', '>', 0);
        } elseif ($stockStatus === 'out') {
            $query->where('stock', '<=', 0);
        } elseif ($stockStatus === 'ok') {
            $query->whereColumn('stock', '>', 'min_stock');
        }

        $items = $query->orderByRaw('(stock - min_stock) ASC')->get();

        $lowStockCount = $items->filter(fn ($i) => $i->stock > 0 && $i->stock <= $i->min_stock)->count();
        $outOfStockCount = $items->filter(fn ($i) => $i->stock <= 0)->count();
        $okCount = $items->filter(fn ($i) => $i->stock > $i->min_stock)->count();

        $productTypes = ProductType::orderBy('name')->get(['uuid', 'name'])->map(fn ($pt) => ['id' => $pt->uuid, 'name' => $pt->name]);

        return response()->json([
            'meta' => ['total' => $items->count()],
            'kpis' => [
                'total_items' => $items->count(),
                'low_stock_count' => $lowStockCount,
                'out_of_stock_count' => $outOfStockCount,
                'ok_count' => $okCount,
            ],
            'table' => $items->map(fn ($i) => [
                'product_name' => $i->product?->name ?? '—',
                'product_type' => $i->product?->productType?->name ?? '—',
                'presentation' => $i->presentation?->name ?? '—',
                'stock' => (float) $i->stock,
                'min_stock' => (float) $i->min_stock,
                'status' => $i->stock <= 0 ? 'out' : ($i->stock <= $i->min_stock ? 'low' : 'ok'),
            ])->values(),
            'filters' => compact('productTypes'),
        ]);
    }

    public function inventoryExport(Request $request): BinaryFileResponse
    {
        $items = ProductPresentation::with(['product.productType', 'presentation'])
            ->where('is_active', true)
            ->when($request->filled('product_type_id'), fn ($q) => $q->whereHas('product.productType', fn ($q2) => $q2->where('uuid', $request->string('product_type_id'))))
            ->orderByRaw('(stock - min_stock) ASC')
            ->get();

        $headings = ['Producto', 'Categoría', 'Presentación', 'Stock actual', 'Stock mínimo', 'Estado'];
        $data = $items->map(fn ($i) => [
            $i->product?->name ?? '—',
            $i->product?->productType?->name ?? '—',
            $i->presentation?->name ?? '—',
            (float) $i->stock,
            (float) $i->min_stock,
            $i->stock <= 0 ? 'Sin stock' : ($i->stock <= $i->min_stock ? 'Stock bajo' : 'OK'),
        ])->toArray();

        return Excel::download(new ReportExport($data, $headings), 'reporte-inventario-'.now()->format('Y-m-d').'.xlsx');
    }

    // ── DAILY CASHES ──────────────────────────────────────────────────────────

    public function dailyCashes(Request $request): JsonResponse
    {
        [$from, $to] = $this->dateRange($request);

        $query = DailyCash::with(['pointOfSale', 'user'])
            ->withSum('payments', 'amount')
            ->withSum(['cashMovements as income_sum' => fn ($q) => $q->whereRelation('cashMovementType', 'is_income', true)], 'amount')
            ->withSum(['cashMovements as expense_sum' => fn ($q) => $q->whereRelation('cashMovementType', 'is_income', false)], 'amount')
            ->whereBetween('opened_at', [$from, $to]);

        if ($request->filled('point_of_sale_id')) {
            $query->whereHas('pointOfSale', fn ($q) => $q->where('uuid', $request->string('point_of_sale_id')));
        }
        if ($request->boolean('only_closed')) {
            $query->where('is_closed', true);
        }

        $cashes = $query->orderByDesc('opened_at')->get();

        $pointsOfSale = PointOfSale::orderBy('name')->get(['uuid', 'name'])->map(fn ($p) => ['id' => $p->uuid, 'name' => $p->name]);

        return response()->json([
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
            'filters' => compact('pointsOfSale'),
        ]);
    }

    public function dailyCashesExport(Request $request): BinaryFileResponse
    {
        [$from, $to] = $this->dateRange($request);

        $cashes = DailyCash::with(['pointOfSale', 'user'])
            ->withSum('payments', 'amount')
            ->withSum(['cashMovements as income_sum' => fn ($q) => $q->whereRelation('cashMovementType', 'is_income', true)], 'amount')
            ->withSum(['cashMovements as expense_sum' => fn ($q) => $q->whereRelation('cashMovementType', 'is_income', false)], 'amount')
            ->whereBetween('opened_at', [$from, $to])
            ->when($request->filled('point_of_sale_id'), fn ($q) => $q->whereHas('pointOfSale', fn ($q2) => $q2->where('uuid', $request->string('point_of_sale_id'))))
            ->orderByDesc('opened_at')
            ->get();

        $headings = ['Punto de venta', 'Usuario', 'Saldo inicial', 'Cobros', 'Ingresos extras', 'Egresos extras', 'Saldo cierre', 'Estado', 'Fecha apertura', 'Fecha cierre'];
        $data = $cashes->map(fn ($dc) => [
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

        return Excel::download(new ReportExport($data, $headings), 'reporte-cajas-'.now()->format('Y-m-d').'.xlsx');
    }

    // ── ORDERS ────────────────────────────────────────────────────────────────

    public function orders(Request $request): JsonResponse
    {
        [$from, $to] = $this->dateRange($request);

        $query = Order::with(['client', 'orderState', 'courier'])
            ->whereBetween('created_at', [$from, $to]);

        if ($request->filled('client_id')) {
            $query->whereHas('client', fn ($q) => $q->where('uuid', $request->string('client_id')));
        }
        if ($request->filled('order_state_id')) {
            $query->whereHas('orderState', fn ($q) => $q->where('uuid', $request->string('order_state_id')));
        }
        if ($request->filled('courier_id')) {
            $query->whereHas('courier', fn ($q) => $q->where('uuid', $request->string('courier_id')));
        }

        $allOrders = $query->orderByDesc('created_at')->get();
        $totalRevenue = $allOrders->sum('total');
        $count = $allOrders->count();

        $byState = $allOrders->groupBy(fn ($o) => $o->orderState?->name ?? '—')
            ->map(fn ($group, $name) => [
                'name' => $name,
                'count' => $group->count(),
                'color' => $group->first()?->orderState?->color ?? '#6366f1',
            ])->values()->sortByDesc('count')->values();

        $clients = Client::orderBy('name')->get(['uuid', 'name'])->map(fn ($c) => ['id' => $c->uuid, 'name' => $c->name]);
        $orderStates = OrderState::orderBy('name')->get(['uuid', 'name'])->map(fn ($s) => ['id' => $s->uuid, 'name' => $s->name]);
        $couriers = Courier::orderBy('name')->get(['uuid', 'name'])->map(fn ($c) => ['id' => $c->uuid, 'name' => $c->name]);

        return response()->json([
            'meta' => ['date_from' => $from->toDateString(), 'date_to' => $to->toDateString(), 'total' => $count],
            'kpis' => [
                'count' => $count,
                'total_revenue' => (float) $totalRevenue,
                'avg_ticket' => $count > 0 ? round($totalRevenue / $count, 2) : 0,
                'states_count' => $byState->count(),
            ],
            'by_state' => $byState,
            'table' => $allOrders->map(fn ($o) => [
                'id' => $o->uuid,
                'client' => $o->client?->name ?? '—',
                'state' => $o->orderState?->name,
                'state_color' => $o->orderState?->color ?? '#6366f1',
                'courier' => $o->courier?->name ?? '—',
                'total' => (float) $o->total,
                'delivery_date' => $o->delivery_date?->toDateString(),
                'created_at' => $o->created_at->toISOString(),
            ])->values(),
            'filters' => compact('clients', 'orderStates', 'couriers'),
        ]);
    }

    public function ordersExport(Request $request): BinaryFileResponse
    {
        [$from, $to] = $this->dateRange($request);

        $orders = Order::with(['client', 'orderState', 'courier'])
            ->whereBetween('created_at', [$from, $to])
            ->when($request->filled('client_id'), fn ($q) => $q->whereHas('client', fn ($q2) => $q2->where('uuid', $request->string('client_id'))))
            ->when($request->filled('order_state_id'), fn ($q) => $q->whereHas('orderState', fn ($q2) => $q2->where('uuid', $request->string('order_state_id'))))
            ->when($request->filled('courier_id'), fn ($q) => $q->whereHas('courier', fn ($q2) => $q2->where('uuid', $request->string('courier_id'))))
            ->orderByDesc('created_at')
            ->get();

        $headings = ['Fecha', 'Cliente', 'Estado', 'Repartidor', 'Total', 'Fecha entrega'];
        $data = $orders->map(fn ($o) => [
            $o->created_at->format('d/m/Y H:i'),
            $o->client?->name ?? '—',
            $o->orderState?->name ?? '—',
            $o->courier?->name ?? '—',
            (float) $o->total,
            $o->delivery_date?->format('d/m/Y') ?? '—',
        ])->toArray();

        return Excel::download(new ReportExport($data, $headings), 'reporte-pedidos-'.now()->format('Y-m-d').'.xlsx');
    }

    // ── CLIENTS ───────────────────────────────────────────────────────────────

    public function clients(Request $request): JsonResponse
    {
        [$from, $to] = $this->dateRange($request);

        $rows = DB::connection('tenant')
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
            ->orderByDesc('total_revenue')
            ->get();

        $activeClients = $rows->where('sales_count', '>', 0)->count();

        return response()->json([
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
        ]);
    }

    public function clientsExport(Request $request): BinaryFileResponse
    {
        [$from, $to] = $this->dateRange($request);

        $rows = DB::connection('tenant')
            ->table('clients')
            ->select(
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
            ->groupBy('clients.id', 'clients.name', 'clients.email', 'clients.phone')
            ->orderByDesc('total_revenue')
            ->get();

        $headings = ['Cliente', 'Email', 'Teléfono', 'Nro. ventas', 'Ingresos totales', 'Ticket promedio', 'Última venta'];
        $data = $rows->map(fn ($r) => [
            $r->name,
            $r->email ?? '—',
            $r->phone ?? '—',
            (int) $r->sales_count,
            (float) ($r->total_revenue ?? 0),
            round((float) ($r->avg_ticket ?? 0), 2),
            $r->last_sale_at ? Carbon::parse($r->last_sale_at)->format('d/m/Y') : '—',
        ])->toArray();

        return Excel::download(new ReportExport($data, $headings), 'reporte-clientes-'.now()->format('Y-m-d').'.xlsx');
    }

    // ── PURCHASES ─────────────────────────────────────────────────────────────

    public function purchases(Request $request): JsonResponse
    {
        [$from, $to] = $this->dateRange($request);

        $query = Reception::with(['supplier', 'user'])
            ->whereBetween('received_at', [$from, $to]);

        if ($request->filled('supplier_id')) {
            $query->whereHas('supplier', fn ($q) => $q->where('uuid', $request->string('supplier_id')));
        }

        $receptions = $query->orderByDesc('received_at')->get();
        $totalCost = $receptions->sum('total');
        $count = $receptions->count();

        $bySupplier = $receptions->groupBy(fn ($r) => $r->supplier?->name ?? '—')
            ->map(fn ($group, $name) => [
                'name' => $name,
                'total' => (float) $group->sum('total'),
                'count' => $group->count(),
            ])->values()->sortByDesc('total')->values();

        $suppliers = Supplier::orderBy('name')->get(['uuid', 'name'])->map(fn ($s) => ['id' => $s->uuid, 'name' => $s->name]);

        return response()->json([
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
            'filters' => compact('suppliers'),
        ]);
    }

    public function purchasesExport(Request $request): BinaryFileResponse
    {
        [$from, $to] = $this->dateRange($request);

        $receptions = Reception::with(['supplier', 'user'])
            ->whereBetween('received_at', [$from, $to])
            ->when($request->filled('supplier_id'), fn ($q) => $q->whereHas('supplier', fn ($q2) => $q2->where('uuid', $request->string('supplier_id'))))
            ->orderByDesc('received_at')
            ->get();

        $headings = ['Fecha', 'Proveedor', 'Usuario', 'Total', 'Notas'];
        $data = $receptions->map(fn ($r) => [
            $r->received_at?->format('d/m/Y') ?? '—',
            $r->supplier?->name ?? '—',
            $r->user?->name ?? '—',
            (float) $r->total,
            $r->notes ?? '',
        ])->toArray();

        return Excel::download(new ReportExport($data, $headings), 'reporte-compras-'.now()->format('Y-m-d').'.xlsx');
    }
}
