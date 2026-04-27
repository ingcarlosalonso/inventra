<?php

namespace App\Http\Resources\SaleItem;

use App\Enums\SaleItemType;
use App\Models\CompositeProduct;
use App\Models\ProductPresentation;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'item_type' => $this->when(
                $this->saleable_type !== null,
                fn () => SaleItemType::fromMorphType($this->saleable_type)->value,
            ),
            'description' => $this->description,
            'quantity' => (float) $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'discount_type' => $this->discount_type?->value,
            'discount_value' => (float) $this->discount_value,
            'discount_amount' => (float) $this->discount_amount,
            'total' => (float) $this->total,
            'saleable' => $this->when(
                $this->relationLoaded('saleable') && $this->saleable !== null,
                fn () => $this->formatSaleable(),
            ),
        ];
    }

    private function formatSaleable(): ?array
    {
        return match (true) {
            $this->saleable instanceof ProductPresentation => [
                'id' => $this->saleable->uuid,
                'product' => $this->saleable->relationLoaded('product') ? [
                    'id' => $this->saleable->product->uuid,
                    'name' => $this->saleable->product->name,
                ] : null,
                'presentation' => $this->saleable->relationLoaded('presentation') ? [
                    'id' => $this->saleable->presentation->uuid,
                    'name' => $this->saleable->presentation->name,
                ] : null,
            ],
            $this->saleable instanceof CompositeProduct => [
                'id' => $this->saleable->uuid,
                'name' => $this->saleable->name,
                'code' => $this->saleable->code,
            ],
            $this->saleable instanceof Promotion => [
                'id' => $this->saleable->uuid,
                'name' => $this->saleable->name,
                'code' => $this->saleable->code,
            ],
            default => null,
        };
    }
}
