<?php

namespace App\Actions;

use App\Models\ProductPresentation;
use App\Models\User;
use App\Notifications\LowStockNotification;

class CheckLowStockAction
{
    public function handle(ProductPresentation $productPresentation): void
    {
        if ($productPresentation->min_stock <= 0) {
            return;
        }

        if ($productPresentation->stock >= $productPresentation->min_stock) {
            return;
        }

        User::where('is_active', true)
            ->whereNotNull('email')
            ->each(fn (User $user) => $user->notify(new LowStockNotification($productPresentation)));
    }
}
