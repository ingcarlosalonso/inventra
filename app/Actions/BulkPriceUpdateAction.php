<?php

namespace App\Actions;

use App\Models\ProductPresentation;
use Illuminate\Database\Eloquent\Builder;

class BulkPriceUpdateAction
{
    /**
     * @param  array{type: 'percentage'|'fixed', value: float, product_type_id?: int|null, only_active?: bool}  $data
     */
    public function handle(array $data): int
    {
        $query = ProductPresentation::query()
            ->whereHas('product', function (Builder $q) use ($data) {
                if (! empty($data['product_type_id'])) {
                    $q->where('product_type_id', $data['product_type_id']);
                }
                if ($data['only_active'] ?? true) {
                    $q->where('is_active', true);
                }
            });

        $presentations = $query->get();
        $updated = 0;

        foreach ($presentations as $pp) {
            $newPrice = match ($data['type']) {
                'percentage' => round($pp->price * (1 + $data['value'] / 100), 2),
                'fixed' => round(max(0, $pp->price + $data['value']), 2),
                default => $pp->price,
            };

            if ($newPrice !== (float) $pp->price) {
                $pp->update(['price' => $newPrice]);
                $updated++;
            }
        }

        return $updated;
    }
}
