<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public int $tries = 3;
    public function __construct(public array $stock)
    {
        $this->afterCommit();
    }
    public function via(object $notifiable): array
    {
        return ['database','mail'];
    }
    public function toArray(object $notifiable): array
    {
        return $this->stock + ['message' => 'Stock is at or below the reorder level. Arrange replenishment.'];
    }
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())->subject('Low stock: '.$this->stock['name'])
         ->line($this->stock['name'].' ('.$this->stock['sku'].') needs replenishment.')
         ->line('Available: '.$this->stock['quantity'].' | Reorder level: '.$this->stock['reorder_level'])
         ->action('Review product', route('products.show', $this->stock['product_id']));
    }
}
