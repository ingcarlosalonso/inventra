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
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $product = $this->productPresentation->product;
        $presentation = $this->productPresentation->presentation;

        return [
            'type' => 'low_stock',
            'product_id' => $product->uuid,
            'product_name' => $product->name,
            'presentation_name' => $presentation?->name ?? '—',
            'stock' => (float) $this->productPresentation->stock,
            'min_stock' => (float) $this->productPresentation->min_stock,
            'url' => '/products',
        ];
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
