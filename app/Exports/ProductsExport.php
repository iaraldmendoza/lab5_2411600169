<?php

namespace App\Exports;

use App\Services\SearchService;

class ProductsExport
{
    public function headings(): array
    {
        return ['SKU', 'Product', 'Category', 'Supplier', 'Quantity', 'Reorder level', 'Unit price'];
    }

    public function rows(array $filters = []): array
    {
        return app(SearchService::class)->products($filters)->get()->map(fn ($p) => [
            $p->sku, $p->name, $p->category, $p->supplierRecord?->company_name ?? $p->supplier,
            $p->quantity, $p->reorder_level, $p->unit_price,
        ])->all();
    }
}
