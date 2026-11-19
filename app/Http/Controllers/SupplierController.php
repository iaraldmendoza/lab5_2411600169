<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupplierRequest;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\ProcurementCache;
use App\Services\SearchService;
use App\Services\SupplierPerformanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplierController extends Controller
{
    public function index(Request $request, SearchService $search)
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:100', 'active' => 'nullable|in:0,1',
            'archived' => 'nullable|in:1', 'sort' => 'nullable|in:company_name,lead_time_days,created_at',
        ]);
        $suppliers = $search->suppliers($filters)->paginate(12)->withQueryString();

        return view('suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        $this->authorize('manage-suppliers');

        return view('suppliers.create', ['supplier' => new Supplier(['active' => true, 'lead_time_days' => 7, 'payment_terms' => 'Net 30', 'minimum_order_amount' => 0])]);
    }

    public function store(SupplierRequest $request)
    {
        $supplier = Supplier::create($request->validated());
        $supplier->activityLogs()->create(['user_id' => $request->user()->id, 'event' => 'supplier_created']);

        return to_route('suppliers.show', $supplier)->with('success', 'Supplier added successfully.');
    }

    public function show(Supplier $supplier, SupplierPerformanceService $performance)
    {
        $supplier->load('catalogProducts');
        $orders = $supplier->purchaseOrders()->with('createdBy')->latest('id')->take(8)->get();
        $metrics = $performance->forSupplier($supplier);
        $products = Product::orderBy('name')->get();

        return view('suppliers.show', compact('supplier', 'orders', 'metrics', 'products'));
    }

    public function edit(Supplier $supplier)
    {
        $this->authorize('manage-suppliers');

        return view('suppliers.edit', compact('supplier'));
    }

    public function update(SupplierRequest $request, Supplier $supplier)
    {
        DB::transaction(function () use ($request, $supplier) {
            $supplier = Supplier::lockForUpdate()->findOrFail($supplier->id);

            if (!$request->boolean('active') && $supplier->purchaseOrders()->active()->exists()) {
                throw ValidationException::withMessages(['active' => 'Complete or cancel active purchase orders before deactivating this supplier.']);
            }

            $supplier->update($request->validated());
            $supplier->activityLogs()->create(['user_id' => $request->user()->id, 'event' => 'supplier_updated']);
        });

        return to_route('suppliers.show', $supplier)->with('success', 'Supplier updated successfully.');
    }

    public function destroy(Request $request, Supplier $supplier)
    {
        $this->authorize('manage-suppliers');
        DB::transaction(function () use ($request, $supplier) {
            $supplier = Supplier::lockForUpdate()->findOrFail($supplier->id);

            if ($supplier->purchaseOrders()->active()->exists()) {
                throw ValidationException::withMessages(['supplier' => 'This supplier has active purchase orders and cannot be archived.']);
            }

            $supplier->activityLogs()->create(['user_id' => $request->user()->id, 'event' => 'supplier_archived']);
            $supplier->delete();
        });

        return to_route('suppliers.index')->with('success', 'Supplier archived. Historical purchase orders are preserved.');
    }

    public function restore(Request $request, int $supplier)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $record = Supplier::onlyTrashed()->findOrFail($supplier);
        $record->active = false;
        $record->restore();
        $record->activityLogs()->create(['user_id' => $request->user()->id, 'event' => 'supplier_restored']);

        return to_route('suppliers.show', $record)->with('success', 'Supplier restored as inactive. Review the details before activating it.');
    }

    public function agreement(Request $request, Supplier $supplier)
    {
        $this->authorize('manage-suppliers');
        $data = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'unit_cost' => 'required|numeric|min:0.01|max:999999.99|decimal:0,2',
            'minimum_order_quantity' => 'required|integer|between:1,1000000',
            'package_size' => 'required|integer|between:1,10000',
            'lead_time_days' => 'required|integer|between:1,365',
            'preferred' => 'nullable|boolean',
        ]);
        $product = Product::findOrFail($data['product_id']);
        DB::transaction(function () use ($supplier, $product, $data, $request) {
            $supplier->catalogProducts()->syncWithoutDetaching([$product->id => [
                'unit_cost' => $data['unit_cost'], 'minimum_order_quantity' => $data['minimum_order_quantity'],
                'package_size' => $data['package_size'], 'lead_time_days' => $data['lead_time_days'],
            ]]);

            if ($request->boolean('preferred')) {
                $product->update(['supplier_id' => $supplier->id, 'supplier' => $supplier->company_name]);
            }

            $supplier->activityLogs()->create(['user_id' => $request->user()->id, 'event' => 'agreement_updated', 'details' => ['product_id' => $product->id]]);
            ProcurementCache::clear($supplier->id);
        });

        return back()->with('success', 'Supply agreement saved.');
    }

    public function catalog(Supplier $supplier)
    {
        abort_unless($supplier->active, 422);

        return response()->json(['data' => $supplier->catalogProducts()->orderBy('name')->get()->map(fn ($product) => [
            'id' => $product->id, 'name' => $product->name, 'sku' => $product->sku,
            'unit_price' => $product->pivot->unit_cost,
            'minimum_order_quantity' => $product->pivot->minimum_order_quantity,
            'package_size' => $product->pivot->package_size,
            'lead_time_days' => $product->pivot->lead_time_days ?? $supplier->lead_time_days,
        ])]);
    }
}
