<?php

namespace App\Actions;

use App\Models\Client;
use App\Models\CompositeProduct;
use App\Models\Currency;
use App\Models\ProductPresentation;
use App\Models\Promotion;
use App\Models\Quote;
use Illuminate\Support\Facades\DB;

class StoreQuoteAction
{
    public function __construct(
        private readonly BuildSaleItemsData $buildSaleItemsData
    ) {}

    /**
     * @param  array{
     *     client_id: string|null,
     *     currency_id: string|null,
     *     notes: string|null,
     *     discount_type: string|null,
     *     discount_value: float|string|null,
     *     starts_at: string|null,
     *     expires_at: string|null,
     *     items: array<int, array{
     *         item_type: string,
     *         saleable_id: string,
     *         description: string,
     *         quantity: float|string,
     *         unit_price: float|string,
     *         discount_type: string|null,
     *         discount_value: float|string|null,
     *     }>,
     * }  $data
     */
    public function execute(array $data, int $userId): Quote
    {
        return DB::connection('tenant')->transaction(function () use ($data, $userId): Quote {
            $clientId = isset($data['client_id'])
                ? Client::where('uuid', $data['client_id'])->value('id')
                : null;

            $currencyId = isset($data['currency_id'])
                ? Currency::where('uuid', $data['currency_id'])->value('id')
                : null;

            $built = $this->buildSaleItemsData->execute(
                $data['items'],
                $data['discount_type'] ?? null,
                $data['discount_value'] ?? null,
            );

            $quote = Quote::create([
                'client_id' => $clientId,
                'user_id' => $userId,
                'currency_id' => $currencyId,
                'sale_id' => null,
                'subtotal' => $built['subtotal'],
                'discount_type' => $data['discount_type'] ?? null,
                'discount_value' => (float) ($data['discount_value'] ?? 0),
                'discount_amount' => $built['discount_amount'],
                'total' => $built['total'],
                'notes' => $data['notes'] ?? null,
                'starts_at' => $data['starts_at'] ?? null,
                'expires_at' => $data['expires_at'] ?? null,
            ]);

            foreach ($data['items'] as $index => $inputItem) {
                $quote->items()->create($built['items'][$index]);
            }

            $quote->load(['client', 'currency', 'user', 'items']);

            $quote->items->loadMorph('saleable', [
                ProductPresentation::class => ['product', 'presentation'],
                CompositeProduct::class => [],
                Promotion::class => [],
            ]);

            return $quote;
        });
    }
}
