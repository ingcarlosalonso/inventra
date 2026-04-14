<?php

namespace App\Actions;

use App\Models\Quote;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class ConvertQuoteToSaleAction
{
    public function __construct(
        private readonly StoreSaleAction $storeSaleAction
    ) {}

    /**
     * Convert a quote into a sale.
     *
     * @param  array{
     *     point_of_sale_id: string,
     *     sale_state_id: string|null,
     *     daily_cash_id: string|null,
     *     payments: array<int, array{
     *         payment_method_id: string,
     *         currency_id: string|null,
     *         amount: float|string,
     *         exchange_rate: float|string|null,
     *         notes: string|null,
     *     }>,
     * }  $data
     */
    public function execute(Quote $quote, array $data, int $userId): Sale
    {
        return DB::transaction(function () use ($quote, $data, $userId): Sale {
            // Build sale payload from quote data
            $saleData = [
                'client_id' => $quote->client ? $quote->client->uuid : null,
                'point_of_sale_id' => $data['point_of_sale_id'],
                'sale_state_id' => $data['sale_state_id'] ?? null,
                'daily_cash_id' => $data['daily_cash_id'] ?? null,
                'currency_id' => $quote->currency ? $quote->currency->uuid : null,
                'notes' => $quote->notes,
                'discount_type' => $quote->discount_type?->value,
                'discount_value' => (float) $quote->discount_value,
                'items' => $quote->items->map(fn ($item) => [
                    'product_presentation_id' => $item->productPresentation->uuid,
                    'description' => $item->description,
                    'quantity' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'discount_type' => $item->discount_type?->value,
                    'discount_value' => (float) $item->discount_value,
                ])->toArray(),
                'payments' => $data['payments'],
            ];

            $sale = $this->storeSaleAction->execute($saleData, $userId);

            $quote->update(['sale_id' => $sale->id]);

            return $sale;
        });
    }
}
