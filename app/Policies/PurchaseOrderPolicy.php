<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;

class PurchaseOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, PurchaseOrder $order): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['staff', 'manager', 'admin'], true);
    }

    public function update(User $user, PurchaseOrder $order): bool
    {
        return $order->status === 'draft'
            && ($user->isAdmin() || $order->created_by === $user->id);
    }

    public function delete(User $user, PurchaseOrder $order): bool
    {
        return $user->isAdmin() && $order->status === 'draft';
    }

    public function submit(User $user, PurchaseOrder $order): bool
    {
        return $this->update($user, $order);
    }

    public function approve(User $user, PurchaseOrder $order): bool
    {
        if (!$order->canBeApproved() || !$user->isManager() || $order->created_by === $user->id) {
            return false;
        }

        if ($order->requiresSecondApproval() && $order->manager_approved_at) {
            return $user->isAdmin() && $order->manager_approved_by !== $user->id;
        }

        return true;
    }

    public function reject(User $user, PurchaseOrder $order): bool
    {
        return $order->canBeApproved() && $user->isManager() && $order->created_by !== $user->id;
    }

    public function send(User $user, PurchaseOrder $order): bool
    {
        return $user->isManager() && $order->status === 'approved';
    }

    public function receive(User $user, PurchaseOrder $order): bool
    {
        return $user->canReceiveOrders() && $order->canBeReceived();
    }

    public function cancel(User $user, PurchaseOrder $order): bool
    {
        return $user->isManager()
            && in_array($order->status, ['draft', 'pending', 'approved', 'sent'], true)
            && !$order->items()->where('received_quantity', '>', 0)->exists();
    }
}
