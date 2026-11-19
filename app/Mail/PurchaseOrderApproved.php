<?php

namespace App\Mail;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderApproved extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public string $eventStatus;

    public function __construct(public PurchaseOrder $order)
    {
        $this->eventStatus = $order->status;
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Purchase order approved - '.$this->order->reference);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.purchase-order-approved',
            with: ['order' => $this->order->load('supplier', 'items.product', 'createdBy'), 'eventStatus' => $this->eventStatus]
        );
    }

}
