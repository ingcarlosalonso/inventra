<?php

namespace App\Reports;

use App\Models\ProductType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProductsReport
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

    private function buildQuery(array $filters, Carbon $from, Carbon $to, array $selectColumns)
    {
        $query = DB::connection('tenant')
            ->table('sale_items')
            ->select($selectColumns)
            ->join('product_presentations', 'product_presentations.id', '=', 'sale_items.product_presentation_id')
            ->join('products', 'products.id', '=', 'product_presentations.product_id')
            ->leftJoin('product_types', 'product_types.id', '=', 'products.product_type_id')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereNull('sales.deleted_at')
            ->whereNull('sale_items.deleted_at')
            ->whereBetween('sales.created_at', [$from, $to]);

        if (! empty($filters['product_type_id'])) {
            $productTypeId = ProductType::where('uuid', $filters['product_type_id'])->value('id');
            if ($productTypeId) {
                $query->where('products.product_type_id', $productTypeId);
            }
        }

        return $query;
    }

    public function getData(array $filters): array
    {
        [$from, $to] = $this->dateRange($filters);

        $rows = $this->buildQuery($filters, $from, $to, [
            'products.id as product_id',
            'products.name as product_name',
            'product_types.name as product_type',
            DB::raw('SUM(sale_items.quantity) as units_sold'),
            DB::raw('SUM(sale_items.total) as revenue'),
            DB::raw('AVG(sale_items.unit_price) as avg_price'),
            DB::raw('COUNT(DISTINCT sale_items.sale_id) as sale_count'),
        ])
            ->groupBy('products.id', 'products.name', 'product_types.name')
            ->orderByDesc('revenue')
            ->get();

        $productTypes = ProductType::orderBy('name')->get(['uuid', 'name'])->map(fn ($pt) => ['id' => $pt->uuid, 'name' => $pt->name]);

        return [
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
        ];
    }

    public function getExportData(array $filters): array
    {
        [$from, $to] = $this->dateRange($filters);

        $rows = $this->buildQuery($filters, $from, $to, [
            'products.name as product_name',
            'product_types.name as product_type',
            DB::raw('SUM(sale_items.quantity) as units_sold'),
            DB::raw('SUM(sale_items.total) as revenue'),
            DB::raw('AVG(sale_items.unit_price) as avg_price'),
            DB::raw('COUNT(DISTINCT sale_items.sale_id) as sale_count'),
        ])
            ->groupBy('products.id', 'products.name', 'product_types.name')
            ->orderByDesc('revenue')
            ->get();

        return $rows->map(fn ($r) => [
            $r->product_name,
            $r->product_type ?? '—',
            (float) $r->units_sold,
            (float) $r->revenue,
            round((float) $r->avg_price, 2),
            (int) $r->sale_count,
        ])->toArray();
    }

    public function getHeadings(): array
    {
        return ['Producto', 'Categoría', 'Unidades vendidas', 'Ingresos', 'Precio promedio', 'Nro. ventas'];
    }
}
