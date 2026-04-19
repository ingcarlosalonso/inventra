<?php

namespace App\Notifications;

use App\Models\ProductPresentation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ProductPresentation $productPresentation,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $product = $this->productPresentation->product;
        $presentation = $this->productPresentation->presentation;
        $stock = $this->productPresentation->stock;
        $minStock = $this->productPresentation->min_stock;

        return (new MailMessage)
            ->subject(__('notifications.low_stock_subject', ['product' => $product->name]))
            ->line(__('notifications.low_stock_line', [
                'product' => $product->name,
                'presentation' => $presentation?->name ?? '—',
                'stock' => $stock,
                'min_stock' => $minStock,
            ]))
            ->line(__('notifications.low_stock_action'));
    }
}
