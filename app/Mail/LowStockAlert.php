<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LowStockAlert extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public array $products, public ?string $failure = null)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->failure ? 'Inventory reorder check needs attention' : 'Inventory replenishment summary');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.low-stock-alert');
    }
}
