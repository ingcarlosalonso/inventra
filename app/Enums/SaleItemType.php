<?php

namespace App\Enums;

enum SaleItemType: string
{
    case Product = 'product';
    case Composite = 'composite';
    case Promotion = 'promotion';

    public function morphType(): string
    {
        return match ($this) {
            self::Product => 'product_presentation',
            self::Composite => 'composite_product',
            self::Promotion => 'promotion',
        };
    }

    public static function fromMorphType(string $morphType): self
    {
        return match ($morphType) {
            'product_presentation' => self::Product,
            'composite_product' => self::Composite,
            'promotion' => self::Promotion,
        };
    }
}
