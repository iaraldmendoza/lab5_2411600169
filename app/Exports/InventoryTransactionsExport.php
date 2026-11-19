<?php

namespace App\Exports;

use App\Services\SearchService;

class InventoryTransactionsExport
{
    public function headings(): array
    {
        return ['ID', 'Time', 'Product', 'SKU', 'Type', 'Quantity', 'Balance after', 'User', 'Reference'];
    }

    public function rows(array $filters = []): array
    {
        return app(SearchService::class)->transactions($filters)->get()->map(fn ($t) => [
            $t->id, $t->created_at->toDateTimeString(), $t->product->name, $t->product->sku,
            $t->type, $t->quantity, $t->balance_after, $t->user->name, $t->reference,
        ])->all();
    }
}
