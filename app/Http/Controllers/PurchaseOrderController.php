<?php

namespace App\Http\Controllers;

use App\Http\Requests\GoodsReceiptRequest;
use App\Http\Requests\PurchaseOrderRequest;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\PurchaseOrderService;
use App\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PurchaseOrderController extends Controller
{
    public function index(Request $request, SearchService $search)
    {
        $this->authorize('viewAny', PurchaseOrder::class);
        $filters = $request->validate([
            'q' => 'nullable|string|max:100', 'status' => ['nullable', Rule::in(PurchaseOrder::STATUSES)],
            'supplier_id' => 'nullable|integer|exists:suppliers,id', 'from' => 'nullable|date',
            'to' => ['nullable', 'date', Rule::when($request->filled('from'), 'after_or_equal:from')],
        ]);
        $orders = $search->orders($filters)->paginate(15)->withQueryString();
        $suppliers = Supplier::withTrashed()->orderBy('company_name')->get();

        return view('purchase-orders.index', compact('orders', 'suppliers'));
    }

    public function create()
    {
        $this->authorize('create', PurchaseOrder::class);

        return view('purchase-orders.create', [
            'order' => new PurchaseOrder(['order_date' => today(), 'expected_delivery_date' => today()->addDays(7), 'tax_rate' => 0]),
            'suppliers' => Supplier::active()->orderBy('company_name')->get(),
        ]);
    }

    public function store(PurchaseOrderRequest $request, PurchaseOrderService $service)
    {
        $this->authorize('create', PurchaseOrder::class);
        $order = $service->create($request->validated(), $request->user());

        return to_route('purchase-orders.show', $order)->with('success', 'Purchase order created successfully.');
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('view', $purchaseOrder);
        $purchaseOrder->load(['supplier', 'items.product', 'createdBy', 'approvedBy', 'managerApprovedBy', 'activityLogs.user', 'receipts.receivedBy', 'receipts.items']);

        return view('purchase-orders.show', ['order' => $purchaseOrder]);
    }

    public function edit(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);
        $purchaseOrder->load('items.product');

        return view('purchase-orders.edit', [
            'order' => $purchaseOrder,
            'suppliers' => Supplier::active()->orderBy('company_name')->get(),
        ]);
    }

    public function update(PurchaseOrderRequest $request, PurchaseOrder $purchaseOrder, PurchaseOrderService $service)
    {
        $this->authorize('update', $purchaseOrder);
        $service->update($purchaseOrder, $request->validated(), $request->user());

        return to_route('purchase-orders.show', $purchaseOrder)->with('success', 'Draft order updated.');
    }

    public function destroy(Request $request, PurchaseOrder $purchaseOrder, PurchaseOrderService $service)
    {
        DB::transaction(function () use ($request, $purchaseOrder, $service) {
            $order = PurchaseOrder::lockForUpdate()->findOrFail($purchaseOrder->id);
            $this->authorize('delete', $order);
            $service->audit($order, $request->user(), 'draft_archived');
            $order->delete();
        });

        return to_route('purchase-orders.index')->with('success', 'Draft archived. Its audit record is preserved.');
    }

    public function action(Request $request, PurchaseOrder $purchaseOrder, string $action, PurchaseOrderService $service)
    {
        abort_unless(in_array($action, ['submit', 'approve', 'reject', 'send', 'cancel'], true), 404);
        $data = $request->validate(['reason' => [$action === 'reject' ? 'required' : 'nullable', 'string', 'max:2000']]);
        $order = $service->transition($purchaseOrder, $action, $request->user(), $data['reason'] ?? null);
        $message = $action === 'approve' && $order->status === 'pending'
            ? 'Manager approval recorded. A different administrator must give final approval.'
            : 'Purchase order updated to '.str_replace('_', ' ', $order->status).'.';

        return back()->with('success', $message);
    }

    public function receive(GoodsReceiptRequest $request, PurchaseOrder $purchaseOrder, PurchaseOrderService $service)
    {
        $receipt = $service->receive($purchaseOrder, $request->validated(), $request->user());

        return back()->with('success', 'Receipt '.$receipt->reference.' recorded. Accepted quantities were added to stock.');
    }
}
