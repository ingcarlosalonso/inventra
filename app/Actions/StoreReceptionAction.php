<?php

namespace App\Actions;

use App\Models\DailyCash;
use App\Models\ProductPresentation;
use App\Models\Reception;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class StoreReceptionAction
{
    /**
     * @param  array{
     *     supplier_id: string|null,
     *     daily_cash_id: string|null,
     *     supplier_invoice: string|null,
     *     notes: string|null,
     *     received_at: string,
     *     items: array<int, array{product_presentation_id: string, quantity: float|string, unit_cost: float|string}>
     * }  $data
     */
    public function execute(array $data, int $userId): Reception
    {
        return DB::transaction(function () use ($data, $userId): Reception {
            $supplierId = isset($data['supplier_id'])
                ? Supplier::where('uuid', $data['supplier_id'])->value('id')
                : null;

            $dailyCashId = isset($data['daily_cash_id'])
                ? DailyCash::where('uuid', $data['daily_cash_id'])->value('id')
                : null;

            $reception = Reception::create([
                'supplier_id' => $supplierId,
                'daily_cash_id' => $dailyCashId,
                'user_id' => $userId,
                'supplier_invoice' => $data['supplier_invoice'] ?? null,
                'notes' => $data['notes'] ?? null,
                'received_at' => $data['received_at'],
                'total' => 0,
            ]);

            $total = 0;

            foreach ($data['items'] as $item) {
                $productPresentation = ProductPresentation::where('uuid', $item['product_presentation_id'])->firstOrFail();

                $quantity = (float) $item['quantity'];
                $unitCost = (float) $item['unit_cost'];
                $itemTotal = round($quantity * $unitCost, 2);

                $reception->items()->create([
                    'product_presentation_id' => $productPresentation->id,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'total' => $itemTotal,
                ]);

                $productPresentation->increment('stock', $quantity);

                $total += $itemTotal;
            }

            $reception->update(['total' => $total]);

            return $reception->load(['supplier', 'user', 'items.productPresentation.presentation.presentationType', 'items.productPresentation.product']);
        });
    }
}
