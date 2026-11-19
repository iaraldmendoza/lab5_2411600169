<?php

namespace App\Http\Controllers;

use App\Exports\InventoryTransactionsExport;
use App\Exports\ProductsExport;
use App\Exports\PurchaseOrdersExport;
use App\Exports\SupplierPerformanceExport;
use App\Models\PurchaseOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExportController extends Controller
{
    public function download(Request $request, string $type, string $format)
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:100', 'category' => 'nullable|string|max:255',
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'status' => ['nullable', Rule::in(PurchaseOrder::STATUSES)],
            'stock' => 'nullable|in:low,out', 'product_id' => 'nullable|integer|exists:products,id',
            'type' => 'nullable|in:in,out,adjustment', 'user_id' => 'nullable|integer|exists:users,id',
            'from' => 'nullable|date', 'to' => ['nullable', 'date', Rule::when($request->filled('from'), 'after_or_equal:from')],
        ]);
        $export = match ($type) {
            'products' => new ProductsExport(),
            'purchase-orders' => new PurchaseOrdersExport(),
            'transactions' => new InventoryTransactionsExport(),
            'supplier-performance' => new SupplierPerformanceExport(),
            default => abort(404),
        };
        $headings = $export->headings();
        $rows = $export->rows($filters);
        $title = \Illuminate\Support\Str::headline($type);

        if ($format === 'pdf') {
            return Pdf::loadView('exports.report', compact('headings', 'rows', 'title'))
                ->setPaper('a4', 'landscape')->download($type.'-'.today()->toDateString().'.pdf');
        }

        abort_unless($format === 'csv', 404);

        return response()->streamDownload(function () use ($headings, $rows) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, $headings);

            foreach ($rows as $row) {
                $safe = array_map(function ($value) {
                    if (is_string($value) && preg_match('/^[=+@\-\t\r]/', $value)) {
                        return "'".$value;
                    }

                    return $value;
                }, $row);
                fputcsv($stream, $safe);
            }

            fclose($stream);
        }, $type.'-'.today()->toDateString().'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function purchaseOrder(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('view', $purchaseOrder);

        return Pdf::loadView('exports.purchase-order', [
            'order' => $purchaseOrder->load('supplier', 'items.product', 'createdBy', 'approvedBy'),
        ])->download($purchaseOrder->reference.'.pdf');
    }
}
