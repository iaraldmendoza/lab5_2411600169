<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class ProcurementCache
{
    public static function clear(?int $supplierId = null): void
    {
        foreach (['pending-orders', 'recent-orders', 'supplier-performance', 'reorder-alerts'] as $widget) {
            Cache::forget('procurement.widget.'.$widget);
        }

        Cache::forget('supplier.performance.all');

        if ($supplierId) {
            Cache::forget('supplier.performance.'.$supplierId);
        }
    }
}
