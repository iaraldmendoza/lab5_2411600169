<?php

namespace App\Providers;

use App\Events\InventoryChanged;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Observers\PurchaseOrderObserver;
use App\Policies\PurchaseOrderPolicy;
use App\Services\ProcurementCache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class ProcurementServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(PurchaseOrder::class, PurchaseOrderPolicy::class);
        Gate::define('manage-suppliers', fn (User $user) => $user->isManager());
        Gate::define('run-reorders', fn (User $user) => $user->isManager());
        PurchaseOrder::observe(PurchaseOrderObserver::class);
        Supplier::saved(fn (Supplier $supplier) => ProcurementCache::clear($supplier->id));
        Supplier::deleted(fn (Supplier $supplier) => ProcurementCache::clear($supplier->id));
        Event::listen(InventoryChanged::class, fn () => ProcurementCache::clear());
    }
}
