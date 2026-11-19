<?php

namespace App\Exports;

use App\Services\SearchService;

class PurchaseOrdersExport
{
    public function headings(): array
    {
        return ['Reference', 'Supplier', 'Status', 'Order date', 'Expected delivery', 'Subtotal', 'Tax', 'Total', 'Created by'];
    }

    public function rows(array $filters = []): array
    {
        return app(SearchService::class)->orders($filters)->get()->map(fn ($p) => [
            $p->reference, $p->supplier->company_name, $p->status, $p->order_date->toDateString(),
            $p->expected_delivery_date->toDateString(), $p->subtotal, $p->tax_amount, $p->total, $p->createdBy->name,
        ])->all();
    }
}
