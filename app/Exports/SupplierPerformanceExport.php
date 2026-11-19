<?php

namespace App\Exports;

class SupplierPerformanceExport
{
    public function headings(): array
    {
        return ['Supplier', 'Orders', 'Completed', 'On time (%)', 'Average delivery days', 'Quoted lead days', 'Accuracy (%)', 'Order value (PHP)'];
    }

    public function rows(array $filters = []): array
    {
        return collect(app(\App\Services\SupplierPerformanceService::class)->all())->map(fn ($m) => [
            $m['supplier_name'], $m['total_orders'], $m['completed_orders'], $m['on_time_percentage'] ?? 'N/A',
            $m['average_delivery_days'] ?? 'N/A', $m['quoted_lead_days'], $m['order_accuracy'] ?? 'N/A', $m['total_value'],
        ])->all();
    }
}
