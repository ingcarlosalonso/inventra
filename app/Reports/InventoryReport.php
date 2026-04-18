<?php

namespace App\Reports;

use App\Models\ProductPresentation;
use App\Models\ProductType;

class InventoryReport
{
    public function getData(array $filters): array
    {
        $items = $this->buildQuery($filters)->get();

        $lowStockCount = $items->filter(fn ($i) => $i->stock > 0 && $i->stock <= $i->min_stock)->count();
        $outOfStockCount = $items->filter(fn ($i) => $i->stock <= 0)->count();
        $okCount = $items->filter(fn ($i) => $i->stock > $i->min_stock)->count();

        return [
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
            'filters' => [
                'productTypes' => ProductType::orderBy('name')->get(['uuid', 'name'])->map(fn ($pt) => ['id' => $pt->uuid, 'name' => $pt->name]),
            ],
        ];
    }

    public function getExportData(array $filters): array
    {
        return $this->buildQuery($filters)
            ->get()
            ->map(fn ($i) => [
                $i->product?->name ?? '—',
                $i->product?->productType?->name ?? '—',
                $i->presentation?->name ?? '—',
                (float) $i->stock,
                (float) $i->min_stock,
                $i->stock <= 0 ? 'Sin stock' : ($i->stock <= $i->min_stock ? 'Stock bajo' : 'OK'),
            ])->toArray();
    }

    public function getHeadings(): array
    {
        return ['Producto', 'Categoría', 'Presentación', 'Stock actual', 'Stock mínimo', 'Estado'];
    }

    private function buildQuery(array $filters)
    {
        $stockStatus = $filters['stock_status'] ?? 'all';

        $query = ProductPresentation::with(['product.productType', 'presentation'])
            ->where('is_active', true)
            ->when(
                isset($filters['product_type_id']) && $filters['product_type_id'],
                fn ($q) => $q->whereHas('product.productType', fn ($q2) => $q2->where('uuid', $filters['product_type_id']))
            );

        if ($stockStatus === 'low') {
            $query->whereColumn('stock', '<=', 'min_stock')->where('stock', '>', 0);
        } elseif ($stockStatus === 'out') {
            $query->where('stock', '<=', 0);
        } elseif ($stockStatus === 'ok') {
            $query->whereColumn('stock', '>', 'min_stock');
        }

        return $query->orderByRaw('(stock - min_stock) ASC');
    }
}
