<?php

namespace App\Observers;

use App\Mail\PurchaseOrderApproved;
use App\Mail\PurchaseOrderCreated;
use App\Mail\PurchaseOrderSent;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Services\ProcurementCache;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Facades\Mail;

class PurchaseOrderObserver implements ShouldHandleEventsAfterCommit
{
    public function created(PurchaseOrder $order): void
    {
        ProcurementCache::clear($order->supplier_id);
        $recipients = User::whereIn('role', ['manager', 'admin'])->pluck('email')->all();

        if ($recipients) {
            Mail::to($recipients)->queue(new PurchaseOrderCreated($order));
        }
    }

    public function updated(PurchaseOrder $order): void
    {
        ProcurementCache::clear($order->supplier_id);

        if (!$order->wasChanged('status')) {
            return;
        }

        if ($order->status === 'approved') {
            Mail::to($order->createdBy->email)->queue(new PurchaseOrderApproved($order));
        }

        if ($order->status === 'sent') {
            Mail::to($order->supplier->email)->queue(new PurchaseOrderSent($order));
        }
    }

    public function deleted(PurchaseOrder $order): void
    {
        ProcurementCache::clear($order->supplier_id);
    }
}
