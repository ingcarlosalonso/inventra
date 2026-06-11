<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    public function __construct(string $productName, float $requested, float $available)
    {
        parent::__construct(
            __('sales.insufficient_stock', [
                'product' => $productName,
                'requested' => $requested,
                'available' => $available,
            ])
        );
    }
}
