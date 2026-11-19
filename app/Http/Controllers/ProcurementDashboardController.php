<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProcurementWidgetResource;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\ProcurementWidgetService;
use App\Services\ReorderService;
use App\Services\SupplierPerformanceService;
use Illuminate\Http\Request;

class ProcurementDashboardController extends Controller
{
    public function index()
    {
        return view('procurement.dashboard', [
            'activeSuppliers' => Supplier::active()->count(),
            'openOrders' => PurchaseOrder::active()->count(),
            'productCount' => Product::count(),
        ]);
    }

    public function widget(string $widget, ProcurementWidgetService $service)
    {
        return new ProcurementWidgetResource($service->data($widget));
    }

    public function replenishment(ReorderService $service)
    {
        $service->refresh();
        $products = Product::with('supplierRecord', 'suppliers')->orderBy('name')->get();

        return view('procurement.replenishment', compact('products', 'service'));
    }

    public function run(Request $request, ReorderService $service)
    {
        $this->authorize('run-reorders');
        $orders = $service->generatePurchaseOrders($request->user());

        return to_route('purchase-orders.index')->with('success', $orders->count().' draft purchase order(s) created. Existing outstanding orders were included.');
    }

    public function performance(SupplierPerformanceService $service)
    {
        return view('suppliers.performance', ['metrics' => $service->all()]);
    }
}
